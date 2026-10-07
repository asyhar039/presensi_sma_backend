<?php

namespace App\Http\Requests\TeacherSpace;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class DecideLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Teacher) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
