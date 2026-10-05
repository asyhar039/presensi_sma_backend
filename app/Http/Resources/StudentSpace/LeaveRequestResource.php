<?php

namespace App\Http\Resources\StudentSpace;

use App\Models\LeaveRequest;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fmtDate = fn ($value) => $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        $fmtTime = fn ($value) => is_string($value) ? substr($value, 0, 5) : $value;

        return [
            'id' => $this->id,
            'key' => $this->key,
            'type' => $this->type?->keyLabel(),
            'status' => $this->status?->keyLabel(),
            'start_date' => $fmtDate($this->start_date),
            'end_date' => $fmtDate($this->end_date),
            'range_date' => $this->range_date ?? [],
            'date' => $fmtDate($this->date),
            'time_out' => $fmtTime($this->getRawOriginal('time_out') ?? $this->time_out),
            'time_in' => $fmtTime($this->getRawOriginal('time_in') ?? $this->time_in),
            'exit_reason' => $this->exit_reason,
            'destination' => $this->destination,
            'contact_person' => $this->contact_person,
            'estimated_arrival_time' => $fmtTime($this->getRawOriginal('estimated_arrival_time') ?? $this->estimated_arrival_time),
            'late_reason' => $this->late_reason,
            'notes' => $this->notes,
            'attachment' => $this->attachment,
            'current_step' => $this->current_step,
            'approvals' => $this->whenLoaded('approvals', fn () => $this->approvals->map(fn ($a): array => [
                'step' => $a->step,
                'decision' => $a->decision,
                'decided_at' => $a->decided_at?->toISOString(),
                'notes' => $a->notes,
            ])->all()),
            'requested_at' => $this->requested_at?->toISOString(),
        ];
    }
}
