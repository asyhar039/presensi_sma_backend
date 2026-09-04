<?php

namespace App\Rules;

use Closure;
use App\Models\Schedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ActiveSchedule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $schedule = Schedule::find($value);

        if (!$schedule) {
            $fail('Jadwal yang dipilih tidak ditemukan');
            return;
        }

        if (!$schedule->is_active)
        {
            $fail('Jadwal yang dipilih tidak aktif.');
        }
    }
}
