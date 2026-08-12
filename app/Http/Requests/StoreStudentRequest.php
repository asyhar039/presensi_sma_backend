<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ActiveClass;


class StoreStudentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'nis' => [
                'required',
                'string',
                'unique:students,nis',
            ],

            'gender' => [
                'required',
                'in:L,P',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'birth_date' => [
                'required',
                'date',
            ],

            'class_id' => [
                'required',
                new ActiveClass,
            ],
        ];
    }
}
