<?php

namespace App\Models;

use Database\Factories\SchoolZoneFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property list<list<float>> $points
 * @property bool $is_active
 */
#[Fillable(['name', 'points', 'is_active'])]
#[CollectedBy(Collection::class)]
class SchoolZone extends Model
{
    /** @use HasFactory<SchoolZoneFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SchoolZone>  $query
     * @return Builder<SchoolZone>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('name')->orderBy('id');
    }

    /**
     * @param  Builder<SchoolZone>  $query
     * @return Builder<SchoolZone>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<SchoolZone>  $query
     * @return Builder<SchoolZone>
     */
    #[Scope]
    protected function inactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
