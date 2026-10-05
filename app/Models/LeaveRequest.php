<?php

namespace App\Models;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property LeaveRequestTypeEnum $type
 * @property LeaveRequestStatusEnum $status
 * @property string $key
 * @property array<int, string>|null $range_date
 */
#[Fillable([
    'user_id', 'type', 'status', 'key',
    'start_date', 'end_date', 'range_date',
    'date', 'time_out', 'time_in', 'exit_reason', 'destination', 'contact_person',
    'estimated_arrival_time', 'late_reason',
    'notes', 'attachment', 'requested_at',
    'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'rejected_notes',
])]
#[CollectedBy(Collection::class)]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $leaveRequest): void {
            if (empty($leaveRequest->key)) {
                do {
                    $key = Str::random(16);
                } while (static::query()->where('key', $key)->exists());

                $leaveRequest->key = $key;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => LeaveRequestTypeEnum::class,
            'status' => LeaveRequestStatusEnum::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'range_date' => 'array',
            'date' => 'date:Y-m-d',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
