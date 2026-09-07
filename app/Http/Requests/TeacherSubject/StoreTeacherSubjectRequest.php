<?php

namespace App\Http\Requests\TeacherSubject;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherSubjectRequest extends FormRequest
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
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ];
    }
}
