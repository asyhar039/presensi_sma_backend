<?php

namespace App\Http\Resources;

use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeacherSubject
 */
class TeacherSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher' => TeacherResource::make($this->whenLoaded('teacher')),
            'subject' => SubjectResource::make($this->whenLoaded('subject')),
            'academic_year' => AcademicYearResource::make($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
