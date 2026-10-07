<?php

namespace App\Http\Requests\Presence;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class PresenceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Student) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['month' => ['required', 'date_format:Y-m']];
    }
}
