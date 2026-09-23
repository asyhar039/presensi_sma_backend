<?php

namespace App\Services\Setting;

use App\Models\PublicHoliday;
use Illuminate\Database\Eloquent\Collection;

class PublicHolidayService
{
    /**
     * List holidays for the given MM-YYYY state, falling back to the current month on invalid input.
     *
     * @return Collection<int, PublicHoliday>
     */
    public function list(?string $state): Collection
    {
        [$month, $year] = $this->resolveMonthYear($state);

        return PublicHoliday::query()->forMonth($year, $month)->ordered()->get();
    }

    /**
     * @param  array{name: string, date: string}  $data
     */
    public function create(array $data): PublicHoliday
    {
        return PublicHoliday::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PublicHoliday $publicHoliday, array $data): PublicHoliday
    {
        $publicHoliday->fill($data);
        $publicHoliday->save();

        return $publicHoliday->refresh();
    }

    public function delete(PublicHoliday $publicHoliday): void
    {
        $publicHoliday->delete();
    }

    /**
     * @return array{int, int} [month, year]
     */
    public function resolveMonthYear(?string $state): array
    {
        if (is_string($state) && preg_match('/^(0[1-9]|1[0-2])-(\d{4})$/', $state, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        $now = now();

        return [$now->month, $now->year];
    }
}
