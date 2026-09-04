<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Teacher> $teachers
 * @property-read int|null $teachers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mapel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Mapel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teachers()
    {
        return $this->belongsToMany(
            Teacher::class,
            'teacher_mapel'
        );
    }

}
