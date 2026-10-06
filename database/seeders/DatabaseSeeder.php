<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RoomSeeder::class,
            SubjectSeeder::class,
            ScheduleSettingSeeder::class,
            PublicHolidaySeeder::class,
            AcademicYear20262027Seeder::class,
        ]);
    }
}
