<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\Mapel;

class ActiveMapel implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $mapel = Mapel::find($value);

        if (!$mapel || !$mapel->is_active) {
            $fail('Mapel yang dipilih tidak aktif');

        }
    }
}
