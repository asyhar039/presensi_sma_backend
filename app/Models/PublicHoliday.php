<?php

namespace App\Models;

use Database\Factories\PublicHolidayFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $date
 */
#[Fillable(['name', 'date'])]
#[CollectedBy(Collection::class)]
class PublicHoliday extends Model
{
    /** @use HasFactory<PublicHolidayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @param  Builder<PublicHoliday>  $query
     * @return Builder<PublicHoliday>
     */
    #[Scope]
    protected function forMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * @param  Builder<PublicHoliday>  $query
     * @return Builder<PublicHoliday>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('date');
    }
}
