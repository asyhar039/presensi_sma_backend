<?php

namespace App\Models;

use App\Services\StudentSpace\StudentSpaceCache;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $odd_start_date
 * @property string $odd_end_date
 * @property string $even_start_date
 * @property string $even_end_date
 * @property bool $is_active
 */
#[Fillable(['odd_start_date', 'odd_end_date', 'even_start_date', 'even_end_date', 'is_active'])]
#[CollectedBy(Collection::class)]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        $flush = fn (): callable => function (): void {
            StudentSpaceCache::forgetActiveYear();
        };
        static::saved($flush());
        static::deleted($flush());
    }

    protected function casts(): array
    {
        return [
            'odd_start_date' => 'date',
            'odd_end_date' => 'date',
            'even_start_date' => 'date',
            'even_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function label(): string
    {
        $start = (int) $this->odd_start_date->format('Y');
        $end = $this->even_end_date ? (int) $this->even_end_date->format('Y') : $start + 1;

        return "{$start}/{$end}";
    }

    /**
     * @return HasMany<Classroom, $this>
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /**
     * @return HasMany<DutyTeacher, $this>
     */
    public function dutyTeachers(): HasMany
    {
        return $this->hasMany(DutyTeacher::class);
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /**
     * @return HasMany<TeacherSubject, $this>
     */
    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }
}
