<?php

namespace App\Http\Requests\Classroom;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('academic_year_id')) {
            $activeId = AcademicYear::query()->active()->value('id');
            if ($activeId !== null) {
                $this->merge(['academic_year_id' => $activeId]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:64',
                Rule::unique('classrooms', 'name')->where('academic_year_id', $this->input('academic_year_id')),
            ],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
