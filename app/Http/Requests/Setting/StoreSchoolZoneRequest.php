<?php

namespace App\Http\Requests\Setting;

use App\Enums\RoleEnum;
use App\Rules\PolygonPoints;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:32', Rule::unique('school_zones', 'name')],
            'points' => ['required', 'array', 'min:3', new PolygonPoints],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
