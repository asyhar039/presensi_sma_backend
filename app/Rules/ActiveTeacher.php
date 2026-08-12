<?php

namespace App\Rules;

use App\Models\Teacher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;


class ActiveTeacher implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $teacher = Teacher::with('user')->find($value);

        if (!$teacher) {
            $fail('Guru yang dipilih tidak ditemukan');
            return;
        }
        if (!$teacher->user || !$teacher->user->is_active) {
            $fail('Guru yang dipilih tidak aktif');
        }
    }
}
