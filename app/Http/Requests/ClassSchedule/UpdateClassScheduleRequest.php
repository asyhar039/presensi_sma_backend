<?php

namespace App\Http\Requests\ClassSchedule;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassScheduleRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['sometimes', 'integer', 'exists:classrooms,id'],
            'day' => ['sometimes', 'string', Rule::enum(DayEnum::class)],
            'period' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
