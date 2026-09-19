<?php

namespace App\Services\Setting;

use App\Settings\SchoolZoneSettings;

class SchoolZoneSettingService
{
    public function __construct(private SchoolZoneSettings $settings) {}

    /**
     * @return list<array{name: string, points: list<list<float>>}>
     */
    public function all(): array
    {
        return $this->normalizeZones($this->settings->zones ?? []);
    }

    /**
     * @param  list<array{name: string, points: list<list<float>>}>  $zones
     * @return list<array{name: string, points: list<list<float>>}>
     */
    public function replace(array $zones): array
    {
        $normalized = $this->normalizeZones($zones);

        $this->settings->zones = $normalized;
        $this->settings->save();

        return $normalized;
    }

    public function removeByName(string $name): bool
    {
        $remaining = array_values(array_filter(
            $this->all(),
            fn (array $zone): bool => $zone['name'] !== $name
        ));

        if (count($remaining) === count($this->all())) {
            return false;
        }

        $this->settings->zones = $remaining;
        $this->settings->save();

        return true;
    }

    /**
     * @param  list<array{name: string, points: list<list<float>>}>  $zones
     * @return list<array{name: string, points: list<list<float>>}>
     */
    private function normalizeZones(array $zones): array
    {
        return array_map(fn (array $zone): array => [
            'name' => $zone['name'],
            'points' => array_values(array_map(
                fn (mixed $point): array => [(float) $point[0], (float) $point[1]],
                $zone['points'] ?? []
            )),
        ], array_values($zones));
    }
}
