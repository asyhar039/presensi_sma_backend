<?php

namespace App\Http\Requests\TeacherSpace;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class TeacherStudentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Teacher) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'classroom_id' => ['nullable', 'integer', 'min:1'],
            'semester' => ['nullable', 'string', 'in:1,2,odd,even,Odd,Even,ODD,EVEN'],
        ];
    }
}
