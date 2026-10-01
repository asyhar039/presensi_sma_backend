<?php

namespace App\Http\Requests\Classroom;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class AssignHomeroomRequest extends FormRequest
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
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
