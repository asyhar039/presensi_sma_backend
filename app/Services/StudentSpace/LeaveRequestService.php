<?php

namespace App\Services\StudentSpace;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\DataTable\DataTableBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(private StudentSpaceService $space) {}

    public function hasTodayRequest(User $user, LeaveRequestTypeEnum $type): bool
    {
        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereDate('date', Carbon::today()->toDateString())
            ->exists();
    }

    /**
     * @return LengthAwarePaginator<int, LeaveRequest>
     */
    public function paginate(Request $request, User $user): LengthAwarePaginator
    {
        $types = implode(',', LeaveRequestTypeEnum::values());
        $statuses = implode(',', LeaveRequestStatusEnum::values());

        return DataTableBuilder::make(
            LeaveRequest::query()->where('user_id', $user->id),
            $request
        )
            ->searchable(['key'])
            ->sortable(['id' => 'id', 'requested_at' => 'requested_at', 'date' => 'date'])
            ->addFilter('type', ['nullable', 'string', 'in:'.$types], function (Builder $query, string $value): void {
                $query->where('type', $value);
            })
            ->addFilter('status', ['nullable', 'string', 'in:'.$statuses], function (Builder $query, string $value): void {
                $query->where('status', $value);
            })
            ->paginate();
    }

    public function findOwned(User $user, int $id): LeaveRequest
    {
        $leave = LeaveRequest::query()->where('user_id', $user->id)->find($id);

        abort_if($leave === null, 404, 'Leave request not found.');

        return $leave;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSickLeave(User $user, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be after or equal to the start date.']);
        }

        $range = $this->space->schoolDaysBetween($start, $end);

        if ($range === []) {
            throw ValidationException::withMessages(['start_date' => 'No school days found in the selected range.']);
        }

        return LeaveRequest::query()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestTypeEnum::SickLeave,
            'status' => LeaveRequestStatusEnum::Pending,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_date' => $range,
            'notes' => $data['notes'] ?? null,
            'attachment' => $this->storeAttachment($attachment),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEarlyOut(User $user, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $this->assertSchoolDay(today());
        $this->assertNoDuplicate($user, LeaveRequestTypeEnum::EarlyOut);

        return LeaveRequest::query()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestTypeEnum::EarlyOut,
            'status' => LeaveRequestStatusEnum::Pending,
            'date' => Carbon::today()->toDateString(),
            'time_out' => $data['time_out'],
            'time_in' => $data['time_in'],
            'exit_reason' => $data['exit_reason'],
            'destination' => $data['destination'],
            'contact_person' => $data['contact_person'] ?? null,
            'notes' => $data['notes'] ?? null,
            'attachment' => $this->storeAttachment($attachment),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLateArrival(User $user, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $this->assertSchoolDay(today());
        $this->assertNoDuplicate($user, LeaveRequestTypeEnum::LateArrival);

        return LeaveRequest::query()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestTypeEnum::LateArrival,
            'status' => LeaveRequestStatusEnum::Pending,
            'date' => Carbon::today()->toDateString(),
            'estimated_arrival_time' => $data['estimated_arrival_time'],
            'late_reason' => $data['late_reason'],
            'notes' => $data['notes'] ?? null,
            'attachment' => $this->storeAttachment($attachment),
        ]);
    }

    private function assertSchoolDay(Carbon $date): void
    {
        if (! $this->space->isSchoolDay($date->copy()->startOfDay())) {
            throw ValidationException::withMessages(['date' => 'Today is a holiday, leave requests are not allowed.']);
        }
    }

    private function assertNoDuplicate(User $user, LeaveRequestTypeEnum $type): void
    {
        if ($this->hasTodayRequest($user, $type)) {
            throw ValidationException::withMessages(['date' => 'You have already submitted this request today.']);
        }
    }

    private function storeAttachment(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store('leave-requests', 'public');
    }
}
