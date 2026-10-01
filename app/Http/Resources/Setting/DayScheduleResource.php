<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \stdClass
 *
 * @property array{day: string, schedules: list<array{start: string, end: string, is_break: bool}>}
 */
class DayScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'day' => $this->resource['day'],
            'schedules' => array_values($this->resource['schedules'] ?? []),
        ];
    }
}
