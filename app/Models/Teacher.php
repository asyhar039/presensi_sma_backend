<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;

class Teacher extends Model
{
    //
    protected $fillable = [
        'user_id',
        'nip',
        'gender',
        'phone',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function kelas(): HasOne
    {
    return $this->hasOne(SchoolClass::class, 'wali_kelas_id');
    }
}
