<?php

namespace App\Http\Requests\AcademicYear;

use App\Enums\RoleEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAcademicYearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Admin) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'odd_start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'odd_end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:odd_start_date'],
            'even_start_date' => ['required', 'date', 'date_format:Y-m-d', 'after:odd_end_date'],
            'even_end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:even_start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance with custom cross-field rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['odd_start_date', 'even_end_date'])) {
                return;
            }

            $startDate = Carbon::parse($this->input('odd_start_date'));
            $endDate = Carbon::parse($this->input('even_end_date'));

            if ($startDate->diffInYears($endDate) > 2) {
                $validator->errors()->add(
                    'even_end_date',
                    'The academic year span cannot exceed 2 years.'
                );
            }

            $startYear = $startDate->year;
            $endYear = $endDate->year;

            if ($startYear === $endYear) {
                $validator->errors()->add(
                    'even_end_date',
                    'The even semester must end in a different calendar year than the odd semester starts.'
                );
            } elseif ($endYear !== $startYear + 1) {
                $validator->errors()->add(
                    'even_end_date',
                    'The academic year must conclude in the year immediately following its start.'
                );
            }
        });
    }

    /**
     * Get the validated data for the academic year.
     *
     * @return array{odd_start_date: string, odd_end_date: string, even_start_date: string, even_end_date: string, is_active?: bool}
     */
    public function academicYearData(): array
    {
        return $this->validated();
    }
}
