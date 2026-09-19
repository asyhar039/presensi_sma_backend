<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \stdClass
 *
 * @property array{name: string, points: list<list<float>>}
 */
class SchoolZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'points' => array_values(array_map(
                fn (mixed $point): array => [(float) $point[0], (float) $point[1]],
                $this->resource['points'] ?? []
            )),
        ];
    }
}
