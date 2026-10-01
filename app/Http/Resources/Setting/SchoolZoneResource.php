<?php

namespace App\Http\Resources\Setting;

use App\Models\SchoolZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SchoolZone
 */
class SchoolZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'points' => array_values(array_map(
                fn (mixed $point): array => [(float) $point[0], (float) $point[1]],
                $this->points ?? []
            )),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
