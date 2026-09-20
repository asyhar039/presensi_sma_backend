<?php

namespace App\Http\Requests\AcademicYear;

use App\Enums\Enums\SemesterEnums;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'semester' => ['required', Rule::enum(SemesterEnums::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{start_date: string, end_date: string, semester: string, is_active?: bool}
     */
    public function academicYearData(): array
    {
        return $this->validated();
    }
}
