<?php

namespace App\Rules;

use Closure;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ActiveStudent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $student = auth()->user()->student;

        if (!$student) {
            $fail('Akun ini bukan akun siswa');
            return;
        }

        // if(!$student->user || !$student->user->is_active) {
        //     $fail('Siswa yang dipilih tidak aktif');
        // }
        if(!$student->user->is_active) {
            $fail('Siswa yang dipilih tidak aktif');
        }
    }
}
