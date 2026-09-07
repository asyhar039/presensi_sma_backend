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
}
