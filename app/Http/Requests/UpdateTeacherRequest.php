<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id),],
            'password' => ['nullable', 'string', 'min:8'],
            'nip' => ['required', 'string', Rule::unique('teachers', 'nip')->ignore($teacher->id),],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
        ];
    }
}
