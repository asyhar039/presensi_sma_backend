<?php

namespace App\Services\LeaveRequest;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestStepEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\DutyTeacher;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentSpace\StudentSpaceCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestApprovalService
{
    public function activeYearId(): ?int
    {
        return StudentSpaceCache::activeYearId();
    }

    public function homeroomClassroom(User $teacher, ?int $yearId = null): ?Classroom
    {
        $teacherId = $teacher->teacher?->id;
        if ($teacherId === null) {
            return null;
        }
        $yearId = $yearId ?? $this->activeYearId();
        if ($yearId === null) {
            return null;
        }

        return Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();
    }

    public function dutyStatus(User $teacher): array
    {
        $teacherId = $teacher->teacher?->id;
        $yearId = $this->activeYearId();
        $now = now();
        $day = strtolower($now->format('l'));
        $time = $now->format('H:i:s');
        if ($teacherId === null || $yearId === null) {
            return ['has_duty_teacher' => false, 'is_active' => false, 'duty_teacher' => null];
        }
        $slot = DutyTeacher::query()
            ->where('academic_year_id', $yearId)
            ->where('teacher_id', $teacherId)
            ->where('day', $day)
            ->orderBy('start_time')
            ->first();
        if ($slot === null) {
            return ['has_duty_teacher' => false, 'is_active' => false, 'duty_teacher' => null];
        }
        $start = substr((string) $slot->getRawOriginal('start_time'), 0, 8);
        $end = substr((string) $slot->getRawOriginal('end_time'), 0, 8);

        return [
            'has_duty_teacher' => true,
            'is_active' => $time >= $start && $time <= $end,
            'duty_teacher' => ['day' => $day, 'start_time' => substr($start, 0, 5), 'end_time' => substr($end, 0, 5)],
        ];
    }

    public function isDutyTeacherToday(User $teacher): bool
    {
        $teacherId = $teacher->teacher?->id;
        $yearId = $this->activeYearId();
        if ($teacherId === null || $yearId === null) {
            return false;
        }

        return DutyTeacher::query()
            ->where('academic_year_id', $yearId)
            ->where('teacher_id', $teacherId)
            ->where('day', strtolower(now()->format('l')))
            ->exists();
    }

    public function currentClassroomIds(User $teacher, ?int $yearId = null): array
    {
        $teacherId = $teacher->teacher?->id;
        if ($teacherId === null) {
            return [];
        }
        $now = now();
        $query = ClassSchedule::query()
            ->where('teacher_id', $teacherId)
            ->where('day', strtolower($now->format('l')))
            ->whereTime('start_time', '<=', $now->format('H:i:s'))
            ->whereTime('end_time', '>=', $now->format('H:i:s'));
        if ($yearId !== null) {
            $query->whereHas('classroom', fn ($q) => $q->where('academic_year_id', $yearId));
        }

        return $query->pluck('classroom_id')->unique()->values()->all();
    }

    public function resolveEarlyOutClassroom(LeaveRequest $leave): ?Classroom
    {
        // ponytail: exit-slot teacher only; upgrade to full [time_out, time_in] overlap if multi-slot approval needed.
        $timeOut = substr((string) $leave->getRawOriginal('time_out'), 0, 8);
        if ($timeOut === '') {
            return null;
        }
        $student = Student::query()->where('user_id', $leave->user_id)->first();
        if ($student === null) {
            return null;
        }
        $yearId = $this->activeYearId();

        return $student->classrooms()
            ->when($yearId, fn ($q) => $q->where('classrooms.academic_year_id', $yearId))
            ->whereHas('classSchedules', function ($q) use ($timeOut): void {
                $q->where('day', strtolower(now()->format('l')))
                    ->whereTime('start_time', '<=', $timeOut)
                    ->whereTime('end_time', '>=', $timeOut);
            })
            ->first();
    }

    public function ensurePending(LeaveRequest $leave): void
    {
        abort_if($leave->status !== LeaveRequestStatusEnum::Pending, 422, 'Leave request is already decided.');
    }

    public function decide(LeaveRequest $leave, User $decider, LeaveRequestStepEnum $expectedStep, string $decision, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $decider, $expectedStep, $decision, $notes): LeaveRequest {
            $locked = LeaveRequest::query()->whereKey($leave->id)->lockForUpdate()->firstOrFail();
            $this->ensurePending($locked);
            $approval = LeaveRequestApproval::query()
                ->where('leave_request_id', $locked->id)
                ->where('decision', 'pending')
                ->orderBy('id')
                ->firstOrFail();
            if ($approval->step !== $expectedStep->value) {
                throw ValidationException::withMessages(['step' => "This request is waiting for {$approval->step} approval."]);
            }
            $this->authorizeStep($locked, $decider, $expectedStep);
            $now = now();
            if ($decision === 'rejected') {
                $approval->fill(['decision' => 'rejected', 'decided_by' => $decider->id, 'decided_at' => $now, 'notes' => $notes])->save();
                $locked->fill([
                    'status' => LeaveRequestStatusEnum::Rejected,
                    'current_step' => null,
                    'rejected_at' => $now,
                    'rejected_by' => $decider->id,
                    'rejected_notes' => $notes,
                ])->save();

                return $locked->refresh()->load('approvals');
            }
            $approval->fill(['decision' => 'approved', 'decided_by' => $decider->id, 'decided_at' => $now, 'notes' => $notes])->save();
            $next = LeaveRequestApproval::query()
                ->where('leave_request_id', $locked->id)
                ->where('decision', 'pending')
                ->orderBy('id')
                ->first();
            if ($next === null) {
                $locked->fill([
                    'status' => LeaveRequestStatusEnum::Approved,
                    'current_step' => null,
                    'approved_at' => $now,
                    'approved_by' => $decider->id,
                ])->save();
            } else {
                $locked->fill(['current_step' => $next->step])->save();
            }

            return $locked->refresh()->load('approvals');
        });
    }

    private function authorizeStep(LeaveRequest $leave, User $teacher, LeaveRequestStepEnum $step): void
    {
        $teacherId = $teacher->teacher?->id;
        abort_if($teacherId === null, 403, 'Forbidden.');
        if ($step === LeaveRequestStepEnum::Homeroom) {
            $this->authorizeHomeroom($leave, $teacherId);
        } elseif ($step === LeaveRequestStepEnum::DutyTeacher) {
            abort_unless($this->isDutyTeacherToday($teacher), 403, 'Only today duty teacher can decide.');
        } else {
            $this->authorizeSubjectTeacher($leave, $teacherId);
        }
    }

    private function authorizeHomeroom(LeaveRequest $leave, int $teacherId): void
    {
        $yearId = $this->activeYearId();
        abort_if($yearId === null, 403, 'Forbidden.');
        $studentId = Student::query()->where('user_id', $leave->user_id)->value('id');
        abort_if($studentId === null, 403, 'Forbidden.');
        $allowed = Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->whereHas('studentClassrooms', fn ($q) => $q->where('student_id', $studentId))
            ->exists();
        abort_unless($allowed, 403, 'Only the homeroom teacher can decide.');
    }

    private function authorizeSubjectTeacher(LeaveRequest $leave, int $teacherId): void
    {
        if ($leave->type !== LeaveRequestTypeEnum::EarlyOut) {
            abort(403, 'Forbidden.');
        }
        $timeOut = substr((string) $leave->getRawOriginal('time_out'), 0, 8);
        abort_if($timeOut === '', 403, 'Forbidden.');
        $studentId = Student::query()->where('user_id', $leave->user_id)->value('id');
        abort_if($studentId === null, 403, 'Forbidden.');
        $yearId = $this->activeYearId();
        $allowed = ClassSchedule::query()
            ->where('teacher_id', $teacherId)
            ->where('day', strtolower(Carbon::today()->format('l')))
            ->whereTime('start_time', '<=', $timeOut)
            ->whereTime('end_time', '>=', $timeOut)
            ->whereHas('classroom.studentClassrooms', fn ($q) => $q->where('student_id', $studentId))
            ->when($yearId, fn ($q) => $q->whereHas('classroom', fn ($qq) => $qq->where('academic_year_id', $yearId)))
            ->exists();
        abort_unless($allowed, 403, 'Only the scheduled subject teacher can decide.');
    }
}
