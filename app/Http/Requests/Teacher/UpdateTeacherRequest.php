<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
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
        $userId = $this->route('teacher')?->user_id;

        return [
            'identity_number' => ['sometimes', 'string', 'max:64', Rule::unique('users', 'identity_number')->ignore($userId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('users', 'phone_number')->ignore($userId)],
            'gender' => ['sometimes', Rule::enum(GenderEnums::class)],
            'address' => ['sometimes', 'nullable', 'string', 'max:128'],
            'employment_status' => ['sometimes', Rule::enum(TeacherEmploymentStatusEnums::class)],
        ];
    }
}
