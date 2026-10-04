<?php

namespace App\Http\Requests\AcademicYear;

use App\Enums\RoleEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAcademicYearRequest extends FormRequest
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
            'odd_start_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'odd_end_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'even_start_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'even_end_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance with smart partial-update checks.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $academicYear = $this->route('academic_year');

            $oddStart = $this->input('odd_start_date', $academicYear?->odd_start_date);
            $oddEnd = $this->input('odd_end_date', $academicYear?->odd_end_date);
            $evenStart = $this->input('even_start_date', $academicYear?->even_start_date);
            $evenEnd = $this->input('even_end_date', $academicYear?->even_end_date);

            if ($oddStart && $oddEnd && Carbon::parse($oddEnd)->lte(Carbon::parse($oddStart))) {
                $validator->errors()->add('odd_end_date', 'The odd end date must be after the odd start date.');
            }

            if ($oddEnd && $evenStart && Carbon::parse($evenStart)->lte(Carbon::parse($oddEnd))) {
                $validator->errors()->add('even_start_date', 'The even start date must be after the odd end date.');
            }

            if ($evenStart && $evenEnd && Carbon::parse($evenEnd)->lte(Carbon::parse($evenStart))) {
                $validator->errors()->add('even_end_date', 'The even end date must be after the even start date.');
            }

            if ($oddStart && $evenEnd) {
                $startDate = Carbon::parse($oddStart);
                $endDate = Carbon::parse($evenEnd);

                if ($startDate->diffInYears($endDate) > 2) {
                    $validator->errors()->add('even_end_date', 'The academic year span cannot exceed 2 years.');
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
            }
        });
    }

    /**
     * Get the validated data for the academic year.
     *
     * @return array{odd_start_date?: string, odd_end_date?: string, even_start_date?: string, even_end_date?: string, is_active?: bool}
     */
    public function academicYearData(): array
    {
        return $this->validated();
    }
}
