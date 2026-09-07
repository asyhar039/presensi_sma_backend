<?php

namespace App\Models;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'gender', 'address', 'employment_status'])]
#[CollectedBy(Collection::class)]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gender' => GenderEnums::class,
            'employment_status' => TeacherEmploymentStatusEnums::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')->withTimestamps()->withPivot('academic_year_id');
    }

    /**
     * @return HasMany<TeacherSubject, $this>
     */
    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    /**
     * @return HasMany<Classroom, $this>
     */
    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }
}
