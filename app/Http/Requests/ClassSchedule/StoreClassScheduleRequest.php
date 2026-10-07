<?php

namespace App\Http\Requests\ClassSchedule;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassScheduleRequest extends FormRequest
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
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'day' => ['required', 'string', Rule::enum(DayEnum::class)],
            'period' => ['required', 'integer', 'min:0', 'max:30'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
