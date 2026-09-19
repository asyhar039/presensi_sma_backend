<?php

namespace App\Services\Setting;

use App\Enums\DayEnum;
use App\Settings\ScheduleSettings;

class ScheduleSettingService
{
    public function __construct(private ScheduleSettings $settings) {}

    /**
     * @return array<string, list<array{start: string, end: string, is_break: bool}>>
     */
    public function all(): array
    {
        $days = [];

        foreach (DayEnum::values() as $day) {
            $days[$day] = $this->getDay($day);
        }

        return $days;
    }

    /**
     * @return list<array{start: string, end: string, is_break: bool}>
     */
    public function getDay(string $day): array
    {
        return $this->normalizeSchedules($this->settings->days[$day] ?? []);
    }

    /**
     * @param  list<array{start: string, end: string, is_break?: bool}>  $schedules
     * @return list<array{start: string, end: string, is_break: bool}>
     */
    public function updateDay(string $day, array $schedules): array
    {
        $normalized = $this->normalizeSchedules($schedules);

        $days = $this->settings->days;
        $days[$day] = $normalized;
        $this->settings->days = $days;
        $this->settings->save();

        return $normalized;
    }

    /**
     * @param  list<array{start: string, end: string, is_break?: bool}>  $schedules
     * @return list<array{start: string, end: string, is_break: bool}>
     */
    private function normalizeSchedules(array $schedules): array
    {
        $normalized = array_map(fn (array $schedule): array => [
            'start' => $schedule['start'],
            'end' => $schedule['end'],
            'is_break' => (bool) ($schedule['is_break'] ?? false),
        ], array_values($schedules));

        usort($normalized, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        return $normalized;
    }
}
