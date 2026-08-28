<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $superAdmin = Role::findByName('Super Admin', 'web');
        $guru = Role::findByName('Guru', 'web');
        $siswa = Role::findByName('Siswa', 'web');

        //super admin bisa akses semua permission
        $superAdmin->syncPermissions(Permission::all());

        //Permission dinggo guru
        $guru->syncPermissions([
            'view schedules',
            'view attendances',
            'generate attendance qr',
            'view attendance reports',
        ]);

        //Permission dinggo siswa
        $siswa->syncPermissions([
            'view attendances',
        ]);
    }
}
