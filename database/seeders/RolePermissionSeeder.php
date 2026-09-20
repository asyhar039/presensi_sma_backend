<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (RoleEnum::cases() as $role) {
            $roleModel = Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);

            $roleModel->syncPermissions(
                collect($role->permissions())->map(fn (PermissionEnum $permission): string => $permission->value)->all()
            );
        }
    }
}
