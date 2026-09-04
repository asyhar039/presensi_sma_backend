<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property int $id
 * @property int $student_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $reason
 * @property string $document_path
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Teacher|null $approvedBy
 * @property-read \App\Models\Student $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PermissionRequest extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'document_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'approved_by');
    }
}
