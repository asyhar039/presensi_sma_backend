<?php

namespace App\Http\Resources\TeacherSpace;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class TeacherStudentHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');
        $classroom = $this->whenLoaded('classrooms')
            ? $this->classrooms->first()
            : null;

        return [
            'student' => [
                'id' => $this->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'identity_number' => $user?->identity_number,
            ],
            'class' => $classroom ? ['id' => $classroom->id, 'name' => $classroom->name] : null,
            'attendance' => $this->getAttribute('attendance') ?? [
                'total_effective_days' => 0, 'present' => 0, 'sick_leave' => 0,
                'permit' => 0, 'alpha' => 0, 'attendance_percentage' => 0.0,
            ],
        ];
    }
}
