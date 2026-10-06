<?php

namespace App\Http\Resources\Dropdown;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class DropdownOptionResource extends JsonResource
{
    abstract protected function value(): mixed;

    abstract protected function label(): mixed;

    /**
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge([
            'value' => $this->value(),
            'label' => $this->label(),
        ], $this->extra());
    }
}
