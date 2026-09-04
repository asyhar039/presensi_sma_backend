<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>  $this->id,
            'attendance_date' => $this->attendance_date?->format('Y-m-d'),
            'check_in' => $this->check_in,
            'status' => $this->status,
            'student' => [
                'id' => $this->student?->id,
                'nis' => $this->student?->nis,
                'name' => $this->student?->user?->name,
            ],
            'schedule' => [
                'id' => $this->schedule?->id,
                'day' => $this->schedule?->day,
                'start_time' => $this->schedule?->start_time,
                'end_time' => $this->schedule?->end_time,

            'teacher' => [
                'id' => $this->schedule?->teacher?->id,
                'name' => $this->schedule?->teacher?->user?->name,
            ],

            'mapel' => [
                'id' =>$this->schedule?->mapel?->id,
                'name' =>$this->schedule?->mapel?->name,
            ],

            'class' => [
                'id' => $this->schedule?->schoolClass?->id,
                'name' => $this->schedule?->schoolClass?->name,
            ],

            'room' => [
                    'id' => $this->schedule?->room?->id,
                    'name' => $this->schedule?->room?->name,
                    'code' => $this->schedule?->room?->code,
                ],

        ],
        ];

    }
}
