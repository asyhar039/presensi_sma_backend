<?php

namespace App\Http\Resources;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademicYear
 */
class AcademicYearResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'odd_start_date' => $this->odd_start_date?->toDateString(),
            'odd_end_date' => $this->odd_end_date?->toDateString(),
            'even_start_date' => $this->even_start_date?->toDateString(),
            'even_end_date' => $this->even_end_date?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
