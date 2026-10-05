<?php

namespace App\Services\TeacherSpace;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestStepEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use App\Services\LeaveRequest\LeaveRequestApprovalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TeacherLeaveListService
{
    public function __construct(private LeaveRequestApprovalService $approvals) {}

    /**
     * Base query joining the owning student user + active-year classroom in one pass.
     * Classroom resolution is per leave type:
     * - sick_leave: student's active-year classroom (homeroom context).
     * - early_out / late_arrival: today-dated request joined to the student's
     *   classroom; subject-teacher scoping narrows to current slots in the controller.
     */
    public function baseQuery(?int $yearId, ?Classroom $onlyClassroom = null): Builder
    {
        $query = LeaveRequest::query()
            ->select('leave_requests.*')
            ->join('users as student_users', 'student_users.id', '=', 'leave_requests.user_id')
            ->join('students as student_profiles', 'student_profiles.user_id', '=', 'student_users.id')
            ->leftJoin('student_classrooms', 'student_classrooms.student_id', '=', 'student_profiles.id')
            ->leftJoin('classrooms', function ($join) use ($yearId): void {
                $join->on('classrooms.id', '=', 'student_classrooms.classroom_id');
                if ($yearId !== null) {
                    $join->where('classrooms.academic_year_id', '=', $yearId);
                }
            })
            ->with(['user.student', 'approvals'])
            ->addSelect([
                'classrooms.id as classroom_id',
                'classrooms.name as classroom_name',
            ])
            ->withAggregate('user as student_name', 'name')
            ->withAggregate('user as student_identity', 'identity_number');

        if ($onlyClassroom !== null) {
            $query->where('classrooms.id', $onlyClassroom->id);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $options  {types: list<string>, step: LeaveRequestStepEnum|string|null, todayOnly: bool}
     * @return LengthAwarePaginator<int, LeaveRequest>
     */
    public function paginate(Request $request, Builder $query, array $options = []): LengthAwarePaginator
    {
        $types = implode(',', $options['types'] ?? LeaveRequestTypeEnum::values());
        $statuses = implode(',', LeaveRequestStatusEnum::values());
        $step = $options['step'] ?? null;
        $stepValue = $step instanceof LeaveRequestStepEnum ? $step->value : $step;

        if (($options['todayOnly'] ?? false) === true) {
            $query->whereDate('leave_requests.date', today()->toDateString());
        }

        if ($stepValue !== null) {
            $query->where('leave_requests.current_step', $stepValue);
        }

        return DataTableBuilder::make($query, $request)
            ->searchable(['user.name', 'user.identity_number'])
            ->sortable(['id' => 'leave_requests.id', 'requested_at' => 'leave_requests.requested_at', 'date' => 'leave_requests.date'])
            ->addFilter('type', ['nullable', 'string', 'in:'.$types], function (Builder $q, string $value): void {
                $q->where('leave_requests.type', $value);
            })
            ->addFilter('status', ['nullable', 'string', 'in:'.$statuses], function (Builder $q, string $value): void {
                $q->where('leave_requests.status', $value);
            })
            ->paginate();
    }

    /**
     * Homeroom queue: leaves of students in the teacher's active-year class.
     */
    public function homeroomQuery(User $teacher): ?Builder
    {
        $classroom = $this->approvals->homeroomClassroom($teacher);

        if ($classroom === null) {
            return null;
        }

        return $this->baseQuery($classroom->academic_year_id, $classroom);
    }

    /**
     * Duty queue: today early_out + late_arrival requests (any class).
     */
    public function dutyQuery(): Builder
    {
        return $this->baseQuery($this->approvals->activeYearId())
            ->whereIn('leave_requests.type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value]);
    }

    /**
     * Subject-teacher queue: today early_out requests in classes the teacher
     * is currently scheduled to teach (now inside the slot).
     *
     * @return array{query: Builder|null, classroom_ids: list<int>}
     */
    public function subjectEarlyOutQuery(User $teacher): array
    {
        $yearId = $this->approvals->activeYearId();
        $ids = $this->approvals->currentClassroomIds($teacher, $yearId);

        if ($ids === []) {
            return ['query' => null, 'classroom_ids' => []];
        }

        $query = $this->baseQuery($yearId)
            ->where('leave_requests.type', LeaveRequestTypeEnum::EarlyOut->value)
            ->whereIn('classrooms.id', $ids);

        return ['query' => $query, 'classroom_ids' => $ids];
    }

    public function findForHomeroom(User $teacher, int $id): LeaveRequest
    {
        $query = $this->homeroomQuery($teacher);
        abort_if($query === null, 404, 'You are not assigned as a homeroom teacher in the active academic year.');
        $leave = $query->where('leave_requests.id', $id)->first();
        abort_if($leave === null, 404, 'Leave request not found.');

        return $leave;
    }

    public function findForDuty(User $teacher, int $id): LeaveRequest
    {
        abort_unless($this->approvals->isDutyTeacherToday($teacher), 403, 'Only today duty teacher can access.');
        $leave = $this->dutyQuery()->where('leave_requests.id', $id)->first();
        abort_if($leave === null, 404, 'Leave request not found.');
        abort_unless(
            in_array($leave->type->value, [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value], true),
            404,
            'Leave request not found.'
        );

        return $leave;
    }

    public function findForSubjectTeacher(User $teacher, int $id): LeaveRequest
    {
        ['query' => $query] = $this->subjectEarlyOutQuery($teacher);
        abort_if($query === null, 404, 'No class is scheduled for you right now.');
        $leave = $query->where('leave_requests.id', $id)->first();
        abort_if($leave === null, 404, 'Leave request not found.');

        return $leave;
    }
}
