<?php

namespace App\Http\Resources\TeacherSpace;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassSchedule
 */
class TeacherScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $yearId = $this->classroom?->academic_year_id;

        $subjects = $this->whenLoaded('teacher') && $this->teacher->relationLoaded('subjects')
            ? $this->teacher->subjects
                ->when($yearId !== null, fn ($subjects) => $subjects->where('pivot.academic_year_id', $yearId))
                ->sortBy('name')->values()
                ->map(fn ($subject): array => ['id' => $subject->id, 'name' => $subject->name])->all()
            : [];

        return [
            'id' => $this->id,
            'classroom' => $this->whenLoaded('classroom', fn (): array => [
                'id' => $this->classroom->id,
                'name' => $this->classroom->name,
            ]),
            'day' => $this->day instanceof \BackedEnum ? $this->day->value : $this->day,
            'start_time' => substr((string) $this->getRawOriginal('start_time'), 0, 5),
            'end_time' => substr((string) $this->getRawOriginal('end_time'), 0, 5),
            'subjects' => $subjects,
        ];
    }
}
