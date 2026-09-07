<?php

namespace App\Http\Requests\Student;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\StudentStatusEnums;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
        $userId = $this->route('student')?->user_id;

        return [
            'identity_number' => ['sometimes', 'string', 'max:64', Rule::unique('users', 'identity_number')->ignore($userId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:32', Rule::unique('users', 'phone_number')->ignore($userId)],
            'gender' => ['sometimes', Rule::enum(GenderEnums::class)],
            'address' => ['sometimes', 'nullable', 'string', 'max:128'],
            'status' => ['sometimes', Rule::enum(StudentStatusEnums::class)],
        ];
    }
}
