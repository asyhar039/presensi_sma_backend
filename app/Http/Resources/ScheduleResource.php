<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'teacher' => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->user->name,
            ],

            'mapel' => [
                'id' => $this->mapel->id,
                'name' => $this->mapel->name,
            ],

            'class' => [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ],

            'room' => [
                'id' => $this->room->id,
                'name' => $this->room->name,
            ],

            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
