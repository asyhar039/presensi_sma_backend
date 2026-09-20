<?php

namespace Database\Seeders;

use App\Enums\Enums\GenderEnums;
use App\Enums\Enums\StudentStatusEnums;
use App\Enums\Enums\TeacherEmploymentStatusEnums;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = $this->seedUser(
                email: 'admin@example.com',
                name: 'Admin User',
                identityNumber: 'ADM001',
                phoneNumber: '081200000001',
            );
            $admin->syncRoles([RoleEnum::Admin->value]);

            $teacherUser = $this->seedUser(
                email: 'teacher@example.com',
                name: 'Teacher User',
                identityNumber: 'TCH001',
                phoneNumber: '081200000002',
            );
            $teacherUser->syncRoles([RoleEnum::Teacher->value]);
            $teacherUser->teacher()->updateOrCreate(
                ['user_id' => $teacherUser->id],
                [
                    'gender' => GenderEnums::Male,
                    'address' => 'Jl. Pendidikan No. 1',
                    'employment_status' => TeacherEmploymentStatusEnums::PNS,
                ]
            );

            $studentUser = $this->seedUser(
                email: 'student@example.com',
                name: 'Student User',
                identityNumber: 'STD001',
                phoneNumber: '081200000003',
            );
            $studentUser->syncRoles([RoleEnum::Student->value]);
            $studentUser->student()->updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'gender' => GenderEnums::Female,
                    'address' => 'Jl. Pelajar No. 1',
                    'status' => StudentStatusEnums::Active,
                ]
            );
        });
    }

    private function seedUser(string $email, string $name, string $identityNumber, string $phoneNumber): User
    {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'identity_number' => $identityNumber,
                'phone_number' => $phoneNumber,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user->refresh();
    }
}
