<?php

namespace App\Http\Requests\ClassSchedule;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListClassScheduleRequest extends FormRequest
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
        ];
    }

    public function classroomId(): int
    {
        return (int) $this->validated('classroom_id');
    }

    public function day(): string
    {
        return (string) $this->validated('day');
    }
}
