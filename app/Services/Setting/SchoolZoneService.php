<?php

namespace App\Services\Setting;

use App\Models\SchoolZone;
use Illuminate\Database\Eloquent\Collection;

class SchoolZoneService
{
    /**
     * List all zones without pagination, optionally filtered by status.
     *
     * @return Collection<int, SchoolZone>
     */
    public function list(?bool $isActive = null): Collection
    {
        return SchoolZone::query()
            ->when($isActive === true, fn ($query) => $query->active())
            ->when($isActive === false, fn ($query) => $query->inactive())
            ->ordered()
            ->get();
    }

    /**
     * @param  array{name: string, points: list<list<float>>, is_active?: bool}  $data
     */
    public function create(array $data): SchoolZone
    {
        $schoolZone = SchoolZone::query()->create([
            ...$data,
            'points' => $this->normalizePoints($data['points']),
        ]);

        return $schoolZone->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolZone $schoolZone, array $data): SchoolZone
    {
        if (array_key_exists('points', $data)) {
            $data['points'] = $this->normalizePoints($data['points']);
        }

        $schoolZone->fill($data);
        $schoolZone->save();

        return $schoolZone->refresh();
    }

    /**
     * @param  array{is_active: bool}  $data
     */
    public function setActive(SchoolZone $schoolZone, array $data): SchoolZone
    {
        $schoolZone->fill($data);
        $schoolZone->save();

        return $schoolZone->refresh();
    }

    /**
     * @return list<list<float>>
     */
    private function normalizePoints(mixed $points): array
    {
        return array_values(array_map(
            fn (mixed $point): array => [(float) $point[0], (float) $point[1]],
            is_array($points) ? array_values($points) : []
        ));
    }
}
