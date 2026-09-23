<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RealCalendarDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return;
        }

        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            $fail('The :attribute must be a real calendar date.');
        }
    }
}
