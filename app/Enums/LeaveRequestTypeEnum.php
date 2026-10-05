<?php

namespace App\Enums;

enum LeaveRequestTypeEnum: string
{
    case SickLeave = 'sick_leave';
    case EarlyOut = 'early_out';
    case LateArrival = 'late_arrival';

    public function label(): string
    {
        return match ($this) {
            self::SickLeave => 'Sick Leave',
            self::EarlyOut => 'Early Out',
            self::LateArrival => 'Late Arrival',
        };
    }

    public function keyLabel(): array
    {
        return [
            'key' => $this->value,
            'label' => $this->label(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
