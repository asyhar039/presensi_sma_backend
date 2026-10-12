<?php

namespace App\Http\Requests\DutyTeacher;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDutyTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Admin) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('day'))) {
            $this->merge(['day' => strtolower($this->input('day'))]);
        }
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
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'day' => ['required', 'string', Rule::enum(DayEnum::class)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
