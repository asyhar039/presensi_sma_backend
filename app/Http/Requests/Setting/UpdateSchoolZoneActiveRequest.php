<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolZoneActiveRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }
}
