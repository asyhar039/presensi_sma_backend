<?php

namespace App\Http\Requests\Dropdown;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class DropdownSelectedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Admin) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('active_ids');
        if (is_string($ids)) {
            $this->merge(['active_ids' => $ids === '' ? [] : explode(',', $ids)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active_ids' => ['nullable', 'array', 'max:100'],
            'active_ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function activeIds(): array
    {
        return array_map(intval(...), $this->validated('active_ids', []));
    }
}
