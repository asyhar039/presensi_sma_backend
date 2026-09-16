<?php

namespace Database\Seeders;

use App\Enums\Enums\SemesterEnums;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startYear = 2025;
        $currentYear = (int) date('Y');
        $records = [];

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $nextYear = $year + 1;

            $oddStart = "{$year}-07-14";
            $oddEnd = "{$year}-12-19";
            $evenStart = "{$nextYear}-01-05";
            $evenEnd = "{$nextYear}-06-25";

            $records[] = [
                'start_date' => $oddStart,
                'end_date' => $oddEnd,
                'semester' => SemesterEnums::ODD,
                'is_active' => now()->between($oddStart, $oddEnd),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $records[] = [
                'start_date' => $evenStart,
                'end_date' => $evenEnd,
                'semester' => SemesterEnums::EVEN,
                'is_active' => now()->between($evenStart, $evenEnd),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AcademicYear::insert($records);
    }
}
