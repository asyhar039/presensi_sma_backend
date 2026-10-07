<?php

namespace App\Http\Requests\StudentSpace;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

abstract class BaseLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::Student) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function attachmentRules(): array
    {
        return ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'];
    }

    public function attachment(): ?UploadedFile
    {
        $file = $this->file('attachment');

        return $file instanceof UploadedFile ? $file : null;
    }
}
