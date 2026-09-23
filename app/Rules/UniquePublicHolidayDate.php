<?php

namespace App\Rules;

use App\Models\PublicHoliday;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePublicHolidayDate implements ValidationRule
{
    public function __construct(private ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = PublicHoliday::query()->whereDate('date', $value);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}
