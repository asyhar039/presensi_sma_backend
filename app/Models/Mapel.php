<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;

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
