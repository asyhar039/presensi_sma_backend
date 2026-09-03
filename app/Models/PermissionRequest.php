<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


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

    protected function cast(): array
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
