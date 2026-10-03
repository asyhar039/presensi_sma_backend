<?php

namespace Database\Seeders;

use App\Enums\Enums\SemesterEnums;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $records = collect($this->academicYears())->map(fn ($val) => [
            'start_date' => $val['start_date'],
            'end_date' => $val['end_date'],
            'semester' => $val['semester'],
            'is_active' => $now->between(Carbon::parse($val['start_date']), Carbon::parse($val['end_date'])),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        AcademicYear::insert($records);
    }

    /**
     * @return list<array{start_date: string, end_date: string, semester: string}>
     */
    private function academicYears(): array
    {
        return [
            ['start_date' => '2026-08-01', 'end_date' => '2027-01-31', 'semester' => SemesterEnums::ODD],
            ['start_date' => '2027-02-01', 'end_date' => '2027-07-31', 'semester' => SemesterEnums::EVEN],
        ];
    }
}
