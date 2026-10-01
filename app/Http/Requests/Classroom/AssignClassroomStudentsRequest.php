<?php

namespace App\Http\Requests\Classroom;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class AssignClassroomStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Admin) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
        ];
    }
}
