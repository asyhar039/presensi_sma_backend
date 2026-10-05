<?php

namespace App\Http\Requests\TeacherSpace;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Teacher) ?? false;
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
            'day' => ['required', 'string', Rule::enum(DayEnum::class)],
        ];
    }

    public function day(): DayEnum
    {
        return DayEnum::from((string) $this->validated('day'));
    }
}
