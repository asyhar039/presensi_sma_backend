<?php

namespace App\Http\Requests\TeacherSpace;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class TeacherStudentHistoryDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Teacher) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('month') && ! $this->filled('year')) {
            $this->merge(['month' => (int) now()->format('m'), 'year' => (int) now()->format('Y')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    /** @return array{int, int} */
    public function monthYear(): array
    {
        $validated = $this->validated();

        return [(int) $validated['month'], (int) $validated['year']];
    }
}
