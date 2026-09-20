<?php

namespace App\Enums\Enums;

enum SemesterEnums: string
{
    case EVEN = 'even';
    case ODD = 'odd';

    public function label(): string
    {
        return match ($this) {
            self::EVEN => 'Genap',
            self::ODD => 'Ganjil',
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
