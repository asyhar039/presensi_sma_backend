<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceRecord extends Model
{
    protected $fillable = [
        'presence_session_id', 'student_id', 'user_id', 'scanned_at',
        'latitude', 'longitude', 'inside_zone',
    ];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime', 'inside_zone' => 'boolean'];
    }

    /** @return BelongsTo<PresenceSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PresenceSession::class, 'presence_session_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
