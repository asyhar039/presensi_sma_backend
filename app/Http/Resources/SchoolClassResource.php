<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
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
            'name' => $this->name,
            'is_active' => $this->is_active,
            'wali_kelas' => $this->whenLoaded('waliKelas', function () {
                return [
                    'id' => $this->waliKelas->id,
                    'nip' => $this->waliKelas->nip,
                    'name' => $this->waliKelas->user->name,
                    'user' => $this->whenLoaded('waliKelas.user', function () {
                        return [
                            'id' => $this->waliKelas->user->id,
                            'name' => $this->waliKelas->user->name,
                            'email' => $this->waliKelas->user->email,
                        ];
                    }),
                ];
            }),
        ];
    }
}
