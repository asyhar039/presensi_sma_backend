<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AcademicYearSeeder::class,
            RoomSeeder::class,
            SubjectSeeder::class,
            ScheduleSettingSeeder::class,
            PublicHolidaySettingSeeder::class,
        ]);
    }
}
