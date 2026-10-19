<?php

namespace App\Services\Attendance;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Http\Requests\Attendance\AttendanceRecapFilterRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\PresenceRecord;
use App\Models\PresenceSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class AttendanceRecapService
{
    /**
     * Resolve the target academic year.
     */
    public function resolveAcademicYear(?int $yearId = null): ?AcademicYear
    {
        if ($yearId !== null) {
            return AcademicYear::query()->find($yearId);
        }

        return AcademicYear::query()->active()->first();
    }

    /**
     * Normalize semester parameter string.
     */
    public function normalizeSemester(?string $semester): ?string
    {
        return match (strtolower((string) $semester)) {
            '1', 'odd' => 'odd',
            '2', 'even' => 'even',
            default => null,
        };
    }

    /**
     * Resolve date range based on academic year, semester, and month.
     *
     * @return array{Carbon, Carbon}
     */
    public function resolveDateRange(AcademicYear $year, ?string $semester, ?int $month): array
    {
        $today = Carbon::today();

        if ($month !== null && $month >= 1 && $month <= 12) {
            $oddStart = Carbon::parse($year->odd_start_date);
            $evenEnd = Carbon::parse($year->even_end_date);
            $targetYear = ($month >= $oddStart->month && $month <= 12) ? $oddStart->year : $evenEnd->year;
            $start = Carbon::createFromDate($targetYear, $month, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->startOfDay();

            return [$start, $end->min($today)];
        }

        $normalized = $this->normalizeSemester($semester);

        if ($normalized === 'odd') {
            return [
                Carbon::parse($year->odd_start_date)->startOfDay(),
                Carbon::parse($year->odd_end_date)->startOfDay()->min($today),
            ];
        }

        if ($normalized === 'even') {
            return [
                Carbon::parse($year->even_start_date)->startOfDay(),
                Carbon::parse($year->even_end_date)->startOfDay()->min($today),
            ];
        }

        return [
            Carbon::parse($year->odd_start_date)->startOfDay(),
            Carbon::parse($year->even_end_date ?? $year->odd_end_date)->startOfDay()->min($today),
        ];
    }

    /**
     * Resolve the targeted classroom for recap.
     */
    public function resolveClassroom(AttendanceRecapFilterRequest $request, AcademicYear $year): ?Classroom
    {
        $classroomId = $request->integer('classroom_id') ?: null;

        if ($classroomId !== null) {
            return Classroom::query()->whereKey($classroomId)->first();
        }

        $user = $request->user();
        $teacherId = $user?->teacher?->id;

        if ($teacherId !== null) {
            $homeroom = Classroom::query()
                ->where('academic_year_id', $year->id)
                ->where('homeroom_teacher_id', $teacherId)
                ->first();

            if ($homeroom !== null) {
                return $homeroom;
            }

            $teaching = Classroom::query()
                ->where('academic_year_id', $year->id)
                ->whereHas('classSchedules', fn ($q) => $q->where('teacher_id', $teacherId))
                ->first();

            if ($teaching !== null) {
                return $teaching;
            }
        }

        return Classroom::query()
            ->where('academic_year_id', $year->id)
            ->orderBy('name')
            ->first();
    }

    /**
     * Evaluate attendance metrics for all students in a classroom across the date range.
     *
     * @return array{
     *     classroom: Classroom,
     *     student_summaries: list<array<string, mixed>>,
     *     student_summaries_by_id: array<int, array<string, mixed>>,
     *     student_records_by_id: array<int, list<array<string, mixed>>>
     * }
     */
    public function evaluateClassroom(Classroom $classroom, Carbon $start, Carbon $end): array
    {
        $students = Student::query()
            ->with(['user'])
            ->whereHas('studentClassrooms', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->get();

        if ($start->gt($end) || $students->isEmpty()) {
            return [
                'classroom' => $classroom,
                'student_summaries' => [],
                'student_summaries_by_id' => [],
                'student_records_by_id' => [],
            ];
        }

        $sessions = PresenceSession::query()
            ->with([
                'schedule.classroom',
                'schedule.teacher.subjects' => fn ($q) => $q->where('teacher_subjects.academic_year_id', $classroom->academic_year_id),
                'schedule.teacher.user',
            ])
            ->where('classroom_id', $classroom->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('started_at')
            ->get();

        $sessionIds = $sessions->pluck('id')->all();
        $scannedMap = [];

        if ($sessionIds !== []) {
            $records = PresenceRecord::query()
                ->whereIn('presence_session_id', $sessionIds)
                ->get(['id', 'presence_session_id', 'student_id', 'scanned_at', 'created_at', 'updated_at']);

            foreach ($records as $record) {
                $scannedMap[$record->presence_session_id][$record->student_id] = $record;
            }
        }

        $userIds = $students->map(fn (Student $s) => $s->user_id)->filter()->all();
        $leavesByUserAndDate = [];

        if ($userIds !== []) {
            $leaves = LeaveRequest::query()
                ->whereIn('user_id', $userIds)
                ->where('status', LeaveRequestStatusEnum::Approved->value)
                ->where(function ($q) use ($start, $end): void {
                    $q->where(function ($qq) use ($start, $end): void {
                        $qq->where('type', LeaveRequestTypeEnum::SickLeave->value)
                            ->where('start_date', '<=', $end->toDateString())
                            ->where('end_date', '>=', $start->toDateString());
                    })->orWhere(function ($qq) use ($start, $end): void {
                        $qq->whereIn('type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value])
                            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
                    });
                })
                ->get();

            foreach ($leaves as $leave) {
                if ($leave->type === LeaveRequestTypeEnum::SickLeave) {
                    $dates = $leave->range_date ?: [];
                    if ($dates === []) {
                        for ($d = Carbon::parse($leave->start_date); $d->lte(Carbon::parse($leave->end_date)); $d->addDay()) {
                            $dates[] = $d->toDateString();
                        }
                    }
                    foreach ($dates as $dateStr) {
                        $leavesByUserAndDate[$leave->user_id][$dateStr] = [
                            'status' => 'sick',
                            'description' => $leave->notes ?: 'Izin Sakit',
                        ];
                    }
                } else {
                    $dateStr = Carbon::parse($leave->date)->toDateString();
                    $leavesByUserAndDate[$leave->user_id][$dateStr] = [
                        'status' => 'permission',
                        'description' => $leave->notes ?: ($leave->exit_reason ?: ($leave->late_reason ?: 'Izin')),
                    ];
                }
            }
        }

        $studentSummaries = [];
        $studentSummariesById = [];
        $studentRecordsById = [];

        foreach ($students as $student) {
            $presentCount = 0;
            $permissionCount = 0;
            $sickCount = 0;
            $absentCount = 0;
            $records = [];

            foreach ($sessions as $session) {
                $sessionDate = Carbon::parse($session->date)->toDateString();
                $scanRecord = $scannedMap[$session->id][$student->id] ?? null;
                $leaveInfo = $leavesByUserAndDate[$student->user_id][$sessionDate] ?? null;

                if ($scanRecord !== null) {
                    $status = 'present';
                    $description = null;
                    $presentCount++;
                } elseif ($leaveInfo !== null) {
                    $status = $leaveInfo['status'];
                    $description = $leaveInfo['description'];
                    if ($status === 'sick') {
                        $sickCount++;
                    } else {
                        $permissionCount++;
                    }
                } else {
                    $status = 'absent';
                    $description = null;
                    $absentCount++;
                }

                $subject = $session->schedule?->teacher?->subjects?->first();
                $subjectId = $subject?->id ?? null;
                $subjectName = $subject?->name ?? 'Mata Pelajaran';

                $timeStart = $session->schedule ? substr((string) $session->schedule->getRawOriginal('start_time'), 0, 5) : '00:00';
                $timeEnd = $session->schedule ? substr((string) $session->schedule->getRawOriginal('end_time'), 0, 5) : '00:00';

                $records[] = [
                    'id' => $scanRecord?->id ?? $session->id,
                    'student_id' => $student->id,
                    'student' => [
                        'id' => $student->id,
                        'user' => [
                            'id' => $student->user?->id,
                            'identity_number' => $student->user?->identity_number,
                            'name' => $student->user?->name,
                            'email' => $student->user?->email,
                        ],
                        'gender' => $student->gender?->keyLabel(),
                    ],
                    'subject_id' => $subjectId,
                    'subject_name' => $subjectName,
                    'classroom_id' => $classroom->id,
                    'classroom_name' => $classroom->name,
                    'date' => $sessionDate,
                    'time_start' => $timeStart,
                    'time_end' => $timeEnd,
                    'room_name' => $classroom->name,
                    'status' => $status,
                    'description' => $description,
                    'created_at' => $scanRecord?->created_at?->toISOString() ?? $session->created_at?->toISOString(),
                    'updated_at' => $scanRecord?->updated_at?->toISOString() ?? $session->updated_at?->toISOString(),
                ];
            }

            $total = $presentCount + $permissionCount + $sickCount + $absentCount;
            $attendanceRate = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0.0;

            $summaryRow = [
                'student_id' => $student->id,
                'student_name' => $student->user?->name,
                'student_nis' => $student->user?->identity_number,
                'present' => $presentCount,
                'permission' => $permissionCount,
                'sick' => $sickCount,
                'absent' => $absentCount,
                'total' => $total,
                'attendance_rate' => $attendanceRate,
            ];

            $studentSummaries[] = $summaryRow;
            $studentSummariesById[$student->id] = $summaryRow;
            $studentRecordsById[$student->id] = $records;
        }

        return [
            'classroom' => $classroom,
            'student_summaries' => $studentSummaries,
            'student_summaries_by_id' => $studentSummariesById,
            'student_records_by_id' => $studentRecordsById,
        ];
    }

    /**
     * Compute top summary metrics card.
     *
     * @return array{
     *     total_students: int,
     *     average_attendance: float,
     *     total_present: int,
     *     total_permission: int,
     *     total_sick: int,
     *     total_absent: int
     * }
     */
    public function getSummary(AttendanceRecapFilterRequest $request): array
    {
        $year = $this->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        abort_if($year === null, Response::HTTP_NOT_FOUND, 'No active academic year found.');

        $classroom = $this->resolveClassroom($request, $year);
        if ($classroom === null) {
            return [
                'total_students' => 0,
                'average_attendance' => 0.0,
                'total_present' => 0,
                'total_permission' => 0,
                'total_sick' => 0,
                'total_absent' => 0,
            ];
        }

        [$start, $end] = $this->resolveDateRange($year, $request->input('semester'), $request->integer('month') ?: null);
        $evaluation = $this->evaluateClassroom($classroom, $start, $end);
        $summaries = $evaluation['student_summaries'];

        $totalStudents = count($summaries);
        $totalPresent = (int) array_sum(array_column($summaries, 'present'));
        $totalPermission = (int) array_sum(array_column($summaries, 'permission'));
        $totalSick = (int) array_sum(array_column($summaries, 'sick'));
        $totalAbsent = (int) array_sum(array_column($summaries, 'absent'));

        $averageAttendance = $totalStudents > 0
            ? round(array_sum(array_column($summaries, 'attendance_rate')) / $totalStudents, 1)
            : 0.0;

        return [
            'total_students' => $totalStudents,
            'average_attendance' => $averageAttendance,
            'total_present' => $totalPresent,
            'total_permission' => $totalPermission,
            'total_sick' => $totalSick,
            'total_absent' => $totalAbsent,
        ];
    }

    /**
     * Paginate student summaries table.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateStudentSummaries(AttendanceRecapFilterRequest $request): LengthAwarePaginator
    {
        $year = $this->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        abort_if($year === null, Response::HTTP_NOT_FOUND, 'No active academic year found.');

        $classroom = $this->resolveClassroom($request, $year);
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(100, (int) $request->integer('per_page', 10)));

        if ($classroom === null) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        [$start, $end] = $this->resolveDateRange($year, $request->input('semester'), $request->integer('month') ?: null);
        $evaluation = $this->evaluateClassroom($classroom, $start, $end);
        $summaries = $evaluation['student_summaries'];

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->input('search')));
            $summaries = array_values(array_filter($summaries, function (array $item) use ($search): bool {
                return str_contains(strtolower((string) ($item['student_name'] ?? '')), $search)
                    || str_contains(strtolower((string) ($item['student_nis'] ?? '')), $search);
            }));
        }

        $sortBy = (string) $request->input('sortBy', 'student_name');
        $order = strtolower((string) $request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        usort($summaries, function (array $a, array $b) use ($sortBy, $order): int {
            $valA = $a[$sortBy] ?? $a['student_name'] ?? '';
            $valB = $b[$sortBy] ?? $b['student_name'] ?? '';

            $result = is_numeric($valA) && is_numeric($valB)
                ? ($valA <=> $valB)
                : strcasecmp((string) $valA, (string) $valB);

            return $order === 'desc' ? -$result : $result;
        });

        $total = count($summaries);
        $slice = array_slice($summaries, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * Retrieve student detail attendance history records and summary.
     *
     * @return array{
     *     student: array<string, mixed>,
     *     summary: array<string, mixed>,
     *     records: list<array<string, mixed>>
     * }
     */
    public function getStudentDetail(int $studentId, AttendanceRecapFilterRequest $request): array
    {
        $year = $this->resolveAcademicYear($request->integer('academic_year_id') ?: null);
        abort_if($year === null, Response::HTTP_NOT_FOUND, 'No active academic year found.');

        $student = Student::query()->with('user')->find($studentId);
        abort_if($student === null, Response::HTTP_NOT_FOUND, 'Student not found.');

        $classroomId = $request->integer('classroom_id') ?: null;
        $classroom = $classroomId !== null
            ? Classroom::query()->whereKey($classroomId)->first()
            : $student->classrooms()->where('academic_year_id', $year->id)->first() ?? $student->classrooms()->first();

        abort_if($classroom === null, Response::HTTP_NOT_FOUND, 'Classroom not found for this student.');

        [$start, $end] = $this->resolveDateRange($year, $request->input('semester'), $request->integer('month') ?: null);
        $evaluation = $this->evaluateClassroom($classroom, $start, $end);

        $summary = $evaluation['student_summaries_by_id'][$student->id] ?? [
            'student_id' => $student->id,
            'student_name' => $student->user?->name,
            'student_nis' => $student->user?->identity_number,
            'present' => 0,
            'permission' => 0,
            'sick' => 0,
            'absent' => 0,
            'total' => 0,
            'attendance_rate' => 0.0,
        ];

        return [
            'student' => [
                'id' => $student->id,
                'user' => [
                    'id' => $student->user?->id,
                    'identity_number' => $student->user?->identity_number,
                    'name' => $student->user?->name,
                    'email' => $student->user?->email,
                ],
                'gender' => $student->gender?->keyLabel(),
            ],
            'summary' => $summary,
            'records' => $evaluation['student_records_by_id'][$student->id] ?? [],
        ];
    }
}

