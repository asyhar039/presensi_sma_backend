<?php

namespace App\Http\Resources;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Classroom
 */
class ClassroomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'academic_year' => AcademicYearResource::make($this->whenLoaded('academicYear')),
            'homeroom_teacher' => TeacherResource::make($this->whenLoaded('homeroomTeacher')),
            'students_count' => $this->whenCounted('students'),
            'students' => StudentResource::collection($this->whenLoaded('students')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
