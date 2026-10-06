<?php

namespace App\Http\Resources\StudentSpace;

use App\Models\LeaveRequest;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fmtDate = fn ($value) => $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;

        return [
            'id' => $this->id,
            'type' => $this->type?->keyLabel(),
            'status' => $this->status?->keyLabel(),
            'key' => $this->key,
            'range_date' => $this->range_date ?? [],
            'date' => $fmtDate($this->date),
            'requested_at' => $this->requested_at?->toISOString(),
        ];
    }
}
