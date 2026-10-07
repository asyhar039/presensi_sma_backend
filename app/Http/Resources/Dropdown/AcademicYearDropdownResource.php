<?php

namespace App\Http\Resources\Dropdown;

use App\Models\AcademicYear;

/**
 * @mixin AcademicYear
 */
class AcademicYearDropdownResource extends DropdownOptionResource
{
    protected function value(): mixed
    {
        return $this->id;
    }

    protected function label(): mixed
    {
        return $this->resource->label();
    }

    /**
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return [
            'odd_start_date' => $this->odd_start_date?->toDateString(),
            'odd_end_date' => $this->odd_end_date?->toDateString(),
            'even_start_date' => $this->even_start_date?->toDateString(),
            'even_end_date' => $this->even_end_date?->toDateString(),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
