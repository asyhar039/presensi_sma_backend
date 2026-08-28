<?php

namespace App\Rules;

use App\Models\SchoolClass;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ActiveClass implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $schoolClass = SchoolClass::find($value);

        if (!$schoolClass) {
            $fail('Kelas yang dipilih tidak ditemukan.');
            return;
        }

        if (!$schoolClass->is_active) {
            $fail('Kelas yang dipilih tidak aktif.');
        }
    }
}
