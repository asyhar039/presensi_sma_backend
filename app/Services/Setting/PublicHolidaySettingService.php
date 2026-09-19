<?php

namespace App\Services\Setting;

use App\Settings\PublicHolidaySettings;

class PublicHolidaySettingService
{
    public function __construct(private PublicHolidaySettings $settings) {}

    /**
     * @return list<array{name: string, date: string}>
     */
    public function all(): array
    {
        return $this->normalizeHolidays($this->settings->holidays ?? []);
    }

    /**
     * @param  list<array{name: string, date: string}>  $holidays
     * @return list<array{name: string, date: string}>
     */
    public function replace(array $holidays): array
    {
        $normalized = $this->normalizeHolidays($holidays);

        $this->settings->holidays = $normalized;
        $this->settings->save();

        return $normalized;
    }

    public function removeByDate(string $date): bool
    {
        $remaining = array_values(array_filter(
            $this->all(),
            fn (array $holiday): bool => $holiday['date'] !== $date
        ));

        if (count($remaining) === count($this->all())) {
            return false;
        }

        $this->settings->holidays = $remaining;
        $this->settings->save();

        return true;
    }

    /**
     * @param  list<array{name: string, date: string}>  $holidays
     * @return list<array{name: string, date: string}>
     */
    private function normalizeHolidays(array $holidays): array
    {
        $normalized = array_map(fn (array $holiday): array => [
            'name' => $holiday['name'],
            'date' => $holiday['date'],
        ], array_values($holidays));

        usort($normalized, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $normalized;
    }
}
