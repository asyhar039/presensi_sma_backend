<?php

namespace App\Enums\Enums;

enum TeacherEmploymentStatusEnums: string
{
    case PNS = 'pns';
    case PPPK = 'pppk';
    case Honorer = 'honorer';

    public function label(): string
    {
        return match ($this) {
            self::PNS => 'PNS',
            self::PPPK => 'Pegawai Pemerintah',
            self::Honorer => 'Honorer',
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
