<?php

namespace App\Http\Resources\Dropdown;

use App\Models\Teacher;

/**
 * @mixin Teacher
 */
class TeacherDropdownResource extends DropdownOptionResource
{
    protected function value(): mixed
    {
        return $this->id;
    }

    protected function label(): mixed
    {
        return $this->user?->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return ['email' => $this->user?->email];
    }
}
