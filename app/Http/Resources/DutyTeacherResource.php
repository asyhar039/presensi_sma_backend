<?php

namespace App\Http\Resources;

use App\Models\DutyTeacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DutyTeacher
 */
class DutyTeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher' => [
                'id' => $this->teacher?->id,
                'name' => $this->teacher?->user?->name,
                'email' => $this->teacher?->user?->email,
            ],
            'academic_year' => AcademicYearResource::make($this->whenLoaded('academicYear')),
            'day' => $this->day instanceof \BackedEnum ? $this->day->value : $this->day,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
