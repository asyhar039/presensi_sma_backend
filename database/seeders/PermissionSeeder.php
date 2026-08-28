<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permissions = [
            // Teachers
            'view teachers',
            'create teachers',
            'update teachers',
            'delete teachers',
            // Students
            'view students',
            'create students',
            'update students',
            'delete students',
            // Classes
            'view classes',
            'create classes',
            'update classes',
            'delete classes',
            // Subjects
            'view subjects',
            'create subjects',
            'update subjects',
            'delete subjects',
            // Schedules
            'view schedules',
            'create schedules',
            'update schedules',
            'delete schedules',
            // Attendances
            'view attendances',
            'create attendances',
            'update attendances',
            //reports
            'view attendance reports',
            //QR Codes
            'generate attendance qr',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 
            'guard_name' => 'web',
            ]);
        }
    }
}
