<?php

namespace App\Http\Resources;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Classroom
 */
class ClassroomListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $teacher = $this->homeroomTeacher;
        $year = $this->academicYear;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'academic_year' => $year ? [
                'label' => $year->label(),
                'value' => $year->id,
            ] : null,
            'homeroom_teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->user?->name,
                'email' => $teacher->user?->email,
            ] : null,
            'students_count' => (int) ($this->students_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
