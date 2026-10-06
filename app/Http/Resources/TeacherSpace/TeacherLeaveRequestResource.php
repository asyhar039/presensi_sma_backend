<?php

namespace App\Http\Resources\TeacherSpace;

use App\Models\LeaveRequest;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class TeacherLeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fmtDate = fn ($value): mixed => $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'student' => $user ? [
                'id' => $user->relationLoaded('student') ? $user->student?->id : null,
                'name' => $user->name,
                'email' => $user->email,
                'identity_number' => $user->identity_number,
            ] : null,
            'class' => [
                'id' => $this->classroom_id ?? null,
                'name' => $this->classroom_name ?? null,
            ],
            'leave_request' => [
                'id' => $this->id,
                'type' => $this->type?->keyLabel(),
                'status' => $this->status?->keyLabel(),
                'key' => $this->key,
                'current_step' => $this->current_step,
                'range_date' => $this->range_date ?? [],
                'date' => $fmtDate($this->date),
            ],
        ];
    }
}
