<?php

namespace App\Enums\Enums;

enum StudentStatusEnums: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
    case DroppedOut = 'dropped_out';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Graduated => 'Graduated',
            self::DroppedOut => 'Dropped Out',
        };
    }

    public function keyLabel(): array
    {
        return [
            'key' => $this->value,
            'label' => $this->label(),
        ];
    }
}
