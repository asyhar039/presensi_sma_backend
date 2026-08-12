<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ActiveClass;

class UpdateStudentRequest extends FormRequest
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
        $student = $this->route('student');
        return [
            
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student->user_id)],
                'password' => ['nullable', 'string', 'min:8'],
                'nis' => ['required', 'string', Rule::unique('students', 'nis')->ignore($student->id)],
                'gender' => ['required', 'in:L,P'],
                'phone' => ['required', 'string', 'max:20'],
                'birth_date' => ['required', 'date'],
                'class_id' => ['required', new ActiveClass],

        ];
    }
}
