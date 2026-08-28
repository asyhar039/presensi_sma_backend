<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'nip' => $this->nip,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,

            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
