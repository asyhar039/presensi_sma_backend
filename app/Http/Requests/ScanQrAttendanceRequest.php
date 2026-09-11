<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScanQrAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required','string','size:64'],
            'latitude' => ['required','numeric','between:-90,90'],
            'longitude' => ['required','numeric','between:-180,180'],
            'accuracy' => ['required','numeric','min:0'],
        ];
    }
}
