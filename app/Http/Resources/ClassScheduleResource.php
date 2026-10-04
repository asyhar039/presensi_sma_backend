<?php

namespace App\Http\Resources;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassSchedule
 */
class ClassScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $teacher = $this->whenLoaded('teacher') ? $this->teacher : null;

        return [
            'id' => $this->id,
            'classroom_id' => $this->classroom_id,
            'day' => $this->day instanceof \BackedEnum ? $this->day->value : $this->day,
            'period' => (int) $this->period,
            'start_time' => substr((string) $this->getRawOriginal('start_time'), 0, 5),
            'end_time' => substr((string) $this->getRawOriginal('end_time'), 0, 5),
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->user?->name,
                'email' => $teacher->user?->email,
                'subjects' => $teacher->relationLoaded('subjects')
                    ? $teacher->subjects->map(fn ($subject): array => ['id' => $subject->id, 'name' => $subject->name])->values()->all()
                    : [],
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
