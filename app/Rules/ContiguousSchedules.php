<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContiguousSchedules implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            return;
        }

        $ranges = [];

        foreach (array_values($value) as $index => $schedule) {
            if (! is_array($schedule) || ! isset($schedule['start'], $schedule['end'])) {
                return;
            }

            $start = $this->toMinutes($schedule['start']);
            $end = $this->toMinutes($schedule['end']);

            if ($start === null || $end === null) {
                return;
            }

            if ($end <= $start) {
                $fail("Schedule #{$index} end time must be after its start time.");

                return;
            }

            $ranges[] = ['index' => $index, 'start' => $start, 'end' => $end];
        }

        foreach ($ranges as $position => $range) {
            if (! isset($ranges[$position + 1])) {
                break;
            }

            $next = $ranges[$position + 1];

            if ($next['start'] < $range['start']) {
                $fail("Schedule #{$next['index']} must be sorted chronologically after schedule #{$range['index']}.");

                return;
            }

            if ($next['start'] < $range['end']) {
                $fail("Schedule #{$next['index']} overlaps schedule #{$range['index']}.");

                return;
            }

            if ($next['start'] > $range['end']) {
                $fail("Schedule #{$next['index']} must start exactly when schedule #{$range['index']} ends; gaps are not allowed.");

                return;
            }
        }

        $schedules = array_values($value);
        $breaks = 0;
        $lessons = 0;

        foreach ($schedules as $schedule) {
            if (! empty($schedule['is_break'])) {
                $breaks++;
            } else {
                $lessons++;
            }
        }

        if ($breaks < 1) {
            $fail('Schedules must include at least 1 break.');

            return;
        }

        if ($lessons < 4) {
            $fail('Schedules must include at least 4 non-break slots.');

            return;
        }
    }

    private function toMinutes(mixed $time): ?int
    {
        if (! is_string($time) || ! preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return $hours * 60 + $minutes;
    }
}
