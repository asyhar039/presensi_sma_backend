<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'identity_number' => ['nullable', 'string', 'max:64', Rule::unique('users', 'identity_number')->ignore($userId)],
            'phone_number' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array{name: string, email: string, identity_number: ?string, phone_number: ?string}
     */
    public function profileData(): array
    {
        return $this->only(['name', 'email', 'identity_number', 'phone_number']);
    }
}
