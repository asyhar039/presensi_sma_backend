<?php

namespace App\Http\Resources\Dropdown;

use App\Models\Classroom;

/**
 * @mixin Classroom
 */
class ClassroomDropdownResource extends DropdownOptionResource
{
    protected function value(): mixed
    {
        return $this->id;
    }

    protected function label(): mixed
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return ['academic_year_id' => $this->academic_year_id];
    }
}
