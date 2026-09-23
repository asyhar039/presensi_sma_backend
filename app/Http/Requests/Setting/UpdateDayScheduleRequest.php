<?php

namespace App\Http\Requests\Setting;

use App\Enums\DayEnum;
use App\Enums\RoleEnum;
use App\Rules\ContiguousSchedules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDayScheduleRequest extends FormRequest
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
            'day' => ['required', 'string', Rule::enum(DayEnum::class)],
            'schedules' => ['sometimes', 'array', new ContiguousSchedules],
            'schedules.*.start' => ['required', 'date_format:H:i'],
            'schedules.*.end' => ['required', 'date_format:H:i'],
            'schedules.*.is_break' => ['sometimes', 'boolean'],
        ];
    }
}
