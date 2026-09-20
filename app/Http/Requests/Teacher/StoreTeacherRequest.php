<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
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
            'identity_number' => ['required', 'string', 'max:64', 'unique:users,identity_number'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:32', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'gender' => ['required', Rule::enum(GenderEnums::class)],
            'address' => ['nullable', 'string', 'max:128'],
            'employment_status' => ['required', Rule::enum(TeacherEmploymentStatusEnums::class)],
        ];
    }
}
