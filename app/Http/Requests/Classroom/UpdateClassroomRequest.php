<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $classroom = $this->route('classroom');
        $academicYearId = $this->input('academic_year_id', $classroom?->academic_year_id);

        return [
            'name' => [
                'sometimes', 'string', 'max:64',
                Rule::unique('classrooms', 'name')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($classroom?->id),
            ],
            'academic_year_id' => ['sometimes', 'integer', 'exists:academic_years,id'],
            'homeroom_teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
