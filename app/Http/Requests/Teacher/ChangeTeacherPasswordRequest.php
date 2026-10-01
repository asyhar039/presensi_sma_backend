<?php

namespace App\Http\Requests\Teacher;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class ChangeTeacherPasswordRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
