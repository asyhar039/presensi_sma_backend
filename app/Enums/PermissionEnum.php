<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case ViewDashboard = 'view-dashboard';
    case ManageUsers = 'manage-users';
    case ManageTeachers = 'manage-teachers';
    case ManageStudents = 'manage-students';
    case ViewAttendances = 'view-attendances';
    case ManageAttendances = 'manage-attendances';

    public function label(): string
    {
        return match ($this) {
            self::ViewDashboard => 'View dashboard',
            self::ManageUsers => 'Manage users',
            self::ManageTeachers => 'Manage teachers',
            self::ManageStudents => 'Manage students',
            self::ViewAttendances => 'View attendances',
            self::ManageAttendances => 'Manage attendances',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
