<?php

namespace Database\Seeders;

use App\Services\Setting\PublicHolidaySettingService;
use Illuminate\Database\Seeder;

class PublicHolidaySettingSeeder extends Seeder
{
    public function run(): void
    {
        app(PublicHolidaySettingService::class)->replace($this->holidays());
    }

    /**
     * @return list<array{name: string, date: string}>
     */
    private function holidays(): array
    {
        return [
            ['name' => 'Tahun Baru 2026 Masehi', 'date' => '2026-01-01'],
            ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'date' => '2026-01-16'],
            ['name' => 'Tahun Baru Imlek 2577 Kongzili', 'date' => '2026-02-17'],
            ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'date' => '2026-03-19'],
            ['name' => 'Hari Raya Idul Fitri 1447 Hijriah', 'date' => '2026-03-21'],
            ['name' => 'Hari Raya Idul Fitri 1447 Hijriah', 'date' => '2026-03-22'],
            ['name' => 'Wafat Yesus Kristus', 'date' => '2026-04-03'],
            ['name' => 'Hari Paskah', 'date' => '2026-04-05'],
            ['name' => 'Hari Buruh Internasional', 'date' => '2026-05-01'],
            ['name' => 'Kenaikan Yesus Kristus', 'date' => '2026-05-14'],
            ['name' => 'Hari Raya Idul Adha 1447 Hijriah', 'date' => '2026-05-27'],
            ['name' => 'Hari Raya Waisak 2570 BE', 'date' => '2026-05-31'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2026-06-01'],
            ['name' => 'Tahun Baru Islam 1448 Hijriah', 'date' => '2026-06-16'],
            ['name' => 'Hari Kemerdekaan Republik Indonesia', 'date' => '2026-08-17'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2026-08-25'],
            ['name' => 'Hari Raya Natal', 'date' => '2026-12-25'],
            ['name' => 'Tahun Baru 2027 Masehi', 'date' => '2027-01-01'],
            ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'date' => '2027-01-05'],
            ['name' => 'Tahun Baru Imlek 2578 Kongzili', 'date' => '2027-02-06'],
            ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1949)', 'date' => '2027-03-08'],
            ['name' => 'Hari Raya Idul Fitri 1448 Hijriah', 'date' => '2027-03-10'],
            ['name' => 'Hari Raya Idul Fitri 1448 Hijriah', 'date' => '2027-03-11'],
            ['name' => 'Wafat Yesus Kristus', 'date' => '2027-03-26'],
            ['name' => 'Hari Paskah', 'date' => '2027-03-28'],
            ['name' => 'Hari Buruh Internasional', 'date' => '2027-05-01'],
            ['name' => 'Kenaikan Yesus Kristus', 'date' => '2027-05-06'],
            ['name' => 'Hari Raya Idul Adha 1448 Hijriah', 'date' => '2027-05-17'],
            ['name' => 'Hari Raya Waisak 2571 BE', 'date' => '2027-05-20'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2027-06-01'],
            ['name' => 'Tahun Baru Islam 1449 Hijriah', 'date' => '2027-06-06'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2027-08-15'],
            ['name' => 'Hari Kemerdekaan Republik Indonesia', 'date' => '2027-08-17'],
            ['name' => 'Hari Raya Natal', 'date' => '2027-12-25'],
        ];
    }
}
