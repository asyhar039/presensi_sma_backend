<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\Schedule;

class ScheduleConflict implements ValidationRule
{
    public function __construct(
        protected string $field,
        protected mixed $value,
        protected mixed $day,
        protected mixed $startTime,
        protected mixed $endTime,
        protected ?int $ignoreId = null,
    )    {

    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Schedule::where($this->field, $this->value)
            ->where('day', $this->day)
            ->where('is_active', true)
            ->where('start_time', '<', $this->endTime)
            ->where('end_time', '>', $this->startTime);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail("Jadwal bentrok dengan jadwal lain untuk {$this->field} yang dipilih.");
        }
    }
}
