<?php

namespace App\Services\TeacherSpace;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\LeaveRequest;
use App\Models\PresenceRecord;
use App\Models\Student;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use App\Services\Presence\PresenceService;
use App\Services\StudentSpace\StudentSpaceService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TeacherStudentHistoryService
{
    public function __construct(
        private StudentSpaceService $space,
        private PresenceService $presence,
    ) {}

    public function activeYear(): ?AcademicYear
    {
        return $this->space->activeYear();
    }

    /** @return Collection<int, Classroom> */
    public function teacherClassrooms(User $user, ?AcademicYear $year = null): Collection
    {
        return $this->teacherClassroomsQuery($user, $year)->get();
    }

    /** @return LengthAwarePaginator<int, Classroom> */
    public function paginateTeacherClassrooms(Request $request, User $user, AcademicYear $year): LengthAwarePaginator
    {
        return DataTableBuilder::make($this->teacherClassroomsQuery($user, $year), $request)
            ->searchable(['name'])
            ->paginateParams(20)
            ->paginate();
    }

    /** @return Builder<Classroom> */
    private function teacherClassroomsQuery(User $user, ?AcademicYear $year = null): Builder
    {
        $year ??= $this->activeYear();
        $teacherId = $user->teacher?->id;

        $query = Classroom::query()->orderBy('name');

        if ($year === null || $teacherId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('academic_year_id', $year->id)
            ->whereHas('classSchedules', fn (Builder $q): Builder => $q->where('teacher_id', $teacherId));
    }

    public function normalizeSemester(?string $semester): ?string
    {
        return match (strtolower((string) $semester)) {
            '1', 'odd' => 'odd',
            '2', 'even' => 'even',
            default => null,
        };
    }

    /** @return array{Carbon, Carbon} */
    public function resolveRange(AcademicYear $year, ?string $semester): array
    {
        $semester = $this->normalizeSemester($semester);

        if ($semester === 'odd') {
            return [Carbon::parse($year->odd_start_date)->startOfDay(), Carbon::parse($year->odd_end_date)->startOfDay()];
        }

        if ($semester === 'even') {
            return [Carbon::parse($year->even_start_date)->startOfDay(), Carbon::parse($year->even_end_date)->startOfDay()];
        }

        return [Carbon::parse($year->odd_start_date)->startOfDay(), Carbon::parse($year->even_end_date ?? $year->odd_end_date)->startOfDay()];
    }

    /** @return list<string> */
    public function effectiveDays(Carbon $start, Carbon $end): array
    {
        $today = Carbon::today();
        $end = $end->copy()->min($today);

        return $start->gt($end) ? [] : $this->space->schoolDaysBetween($start->copy()->startOfDay(), $end->copy()->startOfDay());
    }

    /** @return LengthAwarePaginator<int, Student> */
    public function paginateStudents(Request $request, AcademicYear $year, array $classroomIds): LengthAwarePaginator
    {
        $classroomId = $request->integer('classroom_id') ?: null;
        $ids = $classroomId !== null ? array_values(array_intersect([$classroomId], $classroomIds)) : $classroomIds;

        if ($ids === []) {
            return Student::query()->whereRaw('1 = 0')->paginate((int) $request->integer('per_page', 10));
        }

        $paginator = DataTableBuilder::make(Student::query()->whereHas('classrooms', function (Builder $q) use ($ids, $year): void {
            $q->whereIn('classrooms.id', $ids)->where('classrooms.academic_year_id', $year->id);
        }), $request)
            ->with(['user', 'classrooms' => fn ($q) => $q->where('classrooms.academic_year_id', $year->id)])
            ->searchable(['user.name', 'user.identity_number'])
            ->sortable([
                'id' => 'id',
                'name' => fn (Builder $q, string $dir): Builder => $q->orderBy(User::select('name')->whereColumn('users.id', 'students.user_id'), $dir),
                'created_at' => 'created_at',
            ])
            ->paginate();

        $this->attachSummaries($paginator->getCollection(), $year, $this->normalizeSemester($request->input('semester')));

        return $paginator;
    }

    public function findScopedStudent(User $teacher, AcademicYear $year, array $classroomIds, int $studentId): ?Student
    {
        return Student::query()
            ->whereKey($studentId)
            ->whereHas('classrooms', fn (Builder $q): Builder => $q->whereIn('classrooms.id', $classroomIds)->where('classrooms.academic_year_id', $year->id))
            ->with(['user', 'classrooms' => fn ($q) => $q->where('classrooms.academic_year_id', $year->id)])
            ->first();
    }

    /** @param Collection<int, Student> $students */
    public function attachSummaries(Collection $students, AcademicYear $year, ?string $semester, ?Carbon $start = null, ?Carbon $end = null): void
    {
        if ($students->isEmpty()) {
            return;
        }

        [$rangeStart, $rangeEnd] = $start !== null && $end !== null ? [$start, $end] : $this->resolveRange($year, $semester);
        $effective = $this->effectiveDays($rangeStart, $rangeEnd);
        $inScope = array_fill_keys($effective, true);
        $summaries = $this->summarize($students, $rangeStart, $rangeEnd, $inScope, count($effective));

        foreach ($students as $student) {
            $student->setAttribute('attendance', $summaries[$student->id] ?? $this->emptySummary(count($effective)));
        }
    }

    /** @param Collection<int, Student> $students @param list<int>|null $scheduleIds @return array<int, array<string, mixed>> */
    private function summarize(Collection $students, Carbon $start, Carbon $end, array $inScope, int $total, ?array $scheduleIds = null): array
    {
        $studentIds = $students->pluck('id')->all();
        $userIds = $students->map(fn (Student $s): ?int => $s->user?->id ?? $s->user_id)->filter()->all();
        $userByStudent = $students->mapWithKeys(fn (Student $s): array => [$s->id => $s->user?->id ?? $s->user_id])->all();

        $presentByStudent = [];
        foreach (PresenceRecord::query()->select('student_id')->selectRaw('DATE(presence_sessions.date) as d')
            ->join('presence_sessions', 'presence_sessions.id', '=', 'presence_records.presence_session_id')
            ->whereIn('presence_records.student_id', $studentIds)
            ->when($scheduleIds !== null, fn (Builder $q): Builder => $q->whereIn('presence_sessions.class_schedule_id', $scheduleIds))
            ->whereBetween('presence_sessions.date', [$start->toDateString(), $end->toDateString()])
            ->get() as $row) {
            if (isset($inScope[$row->d])) {
                $presentByStudent[$row->student_id][$row->d] = true;
            }
        }

        $sickByUser = $this->sickDaysByUser($userIds, $start, $end, $inScope);
        $permitByUser = $this->permitDaysByUser($userIds, $start, $end, $inScope);

        $out = [];
        foreach ($students as $student) {
            $present = $presentByStudent[$student->id] ?? [];
            $sick = $sickByUser[$userByStudent[$student->id] ?? 0] ?? [];
            $permit = $permitByUser[$userByStudent[$student->id] ?? 0] ?? [];
            $presentCount = $sickCount = $permitCount = 0;

            foreach ($inScope as $day => $_) {
                if (isset($sick[$day])) {
                    $sickCount++;
                } elseif (isset($permit[$day])) {
                    $permitCount++;
                } elseif (isset($present[$day])) {
                    $presentCount++;
                }
            }

            $alpha = $total - $presentCount - $sickCount - $permitCount;
            $out[$student->id] = [
                'total_effective_days' => $total,
                'present' => $presentCount,
                'sick_leave' => $sickCount,
                'permit' => $permitCount,
                'alpha' => max($alpha, 0),
                'attendance_percentage' => $total > 0 ? round($presentCount / $total * 100, 2) : 0.0,
            ];
        }

        return $out;
    }

    /** @param list<int> $userIds @return array<int, array<string, true>> */
    private function sickDaysByUser(array $userIds, Carbon $start, Carbon $end, array $inScope): array
    {
        if ($userIds === []) {
            return [];
        }

        $map = [];
        $leaves = LeaveRequest::query()->whereIn('user_id', $userIds)
            ->where('status', LeaveRequestStatusEnum::Approved->value)
            ->where('type', LeaveRequestTypeEnum::SickLeave->value)
            ->where('start_date', '<=', $end->toDateString())->where('end_date', '>=', $start->toDateString())
            ->get(['user_id', 'start_date', 'end_date', 'range_date']);

        foreach ($leaves as $leave) {
            $days = $leave->range_date ?: [];
            if ($days === []) {
                for ($d = Carbon::parse($leave->start_date); $d->lte(Carbon::parse($leave->end_date)); $d->addDay()) {
                    $days[] = $d->toDateString();
                }
            }
            foreach ($days as $day) {
                if (isset($inScope[$day])) {
                    $map[$leave->user_id][$day] = true;
                }
            }
        }

        return $map;
    }

    /** @param list<int> $userIds @return array<int, array<string, true>> */
    private function permitDaysByUser(array $userIds, Carbon $start, Carbon $end, array $inScope): array
    {
        if ($userIds === []) {
            return [];
        }

        $map = [];
        $leaves = LeaveRequest::query()->whereIn('user_id', $userIds)
            ->where('status', LeaveRequestStatusEnum::Approved->value)
            ->whereIn('type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'date']);

        foreach ($leaves as $leave) {
            $day = Carbon::parse($leave->date)->toDateString();
            if (isset($inScope[$day])) {
                $map[$leave->user_id][$day] = true;
            }
        }

        return $map;
    }

    /** @return array<string, mixed> */
    private function emptySummary(int $total): array
    {
        return ['total_effective_days' => $total, 'present' => 0, 'sick_leave' => 0, 'permit' => 0, 'alpha' => $total, 'attendance_percentage' => 0.0];
    }

    /** @param list<int> $classroomIds @return array<string, mixed> */
    public function monthlyDetail(Student $student, User $teacher, AcademicYear $year, array $classroomIds, int $yearNum, int $monthNum): array
    {
        $teacherId = $teacher->teacher?->id;
        $classroomId = $student->classrooms->pluck('id')->intersect($classroomIds)->first()
            ?? $student->classrooms->first()?->id;
        $scheduleIds = $teacherId !== null && $classroomId !== null
            ? ClassSchedule::query()->where('classroom_id', $classroomId)->where('teacher_id', $teacherId)->pluck('id')->all()
            : [];

        [$rangeStart, $rangeEnd] = $this->resolveRange($year, null);
        $effective = $this->effectiveDays($rangeStart, $rangeEnd);

        $student->loadMissing('user');
        $summaries = $this->summarize(new Collection([$student]), $rangeStart, $rangeEnd, array_fill_keys($effective, true), count($effective), $scheduleIds);

        $month = sprintf('%04d-%02d', $yearNum, $monthNum);

        return [
            'summary' => $summaries[$student->id] ?? $this->emptySummary(count($effective)),
            'calendar' => $this->presence->monthlyHistory($student->user, $month, $teacherId, $classroomId),
        ];
    }
}
