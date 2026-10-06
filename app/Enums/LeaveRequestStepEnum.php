<?php

namespace App\Enums;

enum LeaveRequestStepEnum: string
{
    case Homeroom = 'homeroom';
    case SubjectTeacher = 'subject_teacher';
    case DutyTeacher = 'duty_teacher';

    public function label(): string
    {
        return match ($this) {
            self::Homeroom => 'Homeroom Teacher',
            self::SubjectTeacher => 'Subject Teacher',
            self::DutyTeacher => 'Duty Teacher',
        };
    }

    public function keyLabel(): array
    {
        return ['key' => $this->value, 'label' => $this->label()];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Ordered approval pipeline per leave type.
     *
     * @return list<LeaveRequestStepEnum>
     */
    public static function pipelineFor(LeaveRequestTypeEnum $type): array
    {
        return match ($type) {
            LeaveRequestTypeEnum::SickLeave => [self::Homeroom],
            LeaveRequestTypeEnum::LateArrival => [self::DutyTeacher],
            LeaveRequestTypeEnum::EarlyOut => [self::SubjectTeacher, self::DutyTeacher],
        };
    }
}
