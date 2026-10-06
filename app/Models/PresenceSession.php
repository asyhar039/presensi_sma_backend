<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $class_schedule_id
 * @property int $classroom_id
 * @property string $date
 * @property string $status
 */
class PresenceSession extends Model
{
    protected $fillable = [
        'class_schedule_id', 'classroom_id', 'teacher_id', 'date', 'status',
        'current_key', 'previous_key', 'qr_expires_at', 'started_at', 'closed_at',
        'total_students', 'total_present', 'total_sick', 'total_permit',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'qr_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ClassSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<PresenceRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(PresenceRecord::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
