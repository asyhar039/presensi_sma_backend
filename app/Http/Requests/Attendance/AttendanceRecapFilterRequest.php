<?php

namespace App\Http\Requests\Attendance;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRecapFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole([RoleEnum::Admin, RoleEnum::Teacher]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'semester' => ['nullable', 'string', 'in:1,2,odd,even,Odd,Even,ODD,EVEN'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}

