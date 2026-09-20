<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Default permissions granted to this role.
     *
     * @return list<PermissionEnum>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => PermissionEnum::cases(),
            self::Teacher => [
                PermissionEnum::ViewDashboard,
                PermissionEnum::ViewAttendances,
                PermissionEnum::ManageAttendances,
                PermissionEnum::ManageStudents,
            ],
            self::Student => [
                PermissionEnum::ViewDashboard,
                PermissionEnum::ViewAttendances,
            ],
        };
    }
}
