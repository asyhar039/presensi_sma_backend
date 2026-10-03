<?php

namespace Database\Seeders;

use App\Services\Setting\ScheduleSettingService;
use Illuminate\Database\Seeder;

class ScheduleSettingSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ScheduleSettingService::class);

        foreach ($this->days() as $day => $schedules) {
            $service->updateDay($day, $schedules);
        }
    }

    /**
     * @return array<string, list<array{start: string, end: string, is_break: bool}>>
     */
    private function days(): array
    {
        $mondayToThursday = [
            ['start' => '07:00', 'end' => '07:30', 'is_break' => false],    // 0
            ['start' => '07:30', 'end' => '08:00', 'is_break' => false],    // 1
            ['start' => '08:00', 'end' => '08:45', 'is_break' => false],    // 2
            ['start' => '08:45', 'end' => '09:30', 'is_break' => false],    // 3
            ['start' => '09:30', 'end' => '09:45', 'is_break' => true],     // skip, because break
            ['start' => '09:45', 'end' => '10:30', 'is_break' => false],    // 4
            ['start' => '10:30', 'end' => '11:15', 'is_break' => false],    // 5
            ['start' => '11:15', 'end' => '12:00', 'is_break' => false],    // 6
            ['start' => '12:00', 'end' => '12:45', 'is_break' => true],     // skip, because break
            ['start' => '12:45', 'end' => '13:25', 'is_break' => false],    // 7
            ['start' => '13:25', 'end' => '14:05', 'is_break' => false],    // 8
            ['start' => '14:05', 'end' => '14:45', 'is_break' => false],    // 9
            ['start' => '14:45', 'end' => '15:30', 'is_break' => false],    // 10
        ];

        $friday = [
            ['start' => '07:00', 'end' => '07:30', 'is_break' => false],    // 0
            ['start' => '07:30', 'end' => '08:00', 'is_break' => false],    // 1
            ['start' => '08:00', 'end' => '08:35', 'is_break' => false],    // 2
            ['start' => '08:35', 'end' => '09:10', 'is_break' => false],    // 3
            ['start' => '09:10', 'end' => '09:25', 'is_break' => true],     // skip, because break
            ['start' => '09:25', 'end' => '10:00', 'is_break' => false],    // 4
            ['start' => '10:00', 'end' => '10:35', 'is_break' => false],    // 5
            ['start' => '10:35', 'end' => '11:10', 'is_break' => false],    // 6
            ['start' => '11:10', 'end' => '11:45', 'is_break' => false],    // 7
            ['start' => '11:45', 'end' => '12:40', 'is_break' => true],     // skip, because break
            ['start' => '12:40', 'end' => '13:15', 'is_break' => false],    // 8
        ];

        return [
            'monday' => $mondayToThursday,
            'tuesday' => $mondayToThursday,
            'wednesday' => $mondayToThursday,
            'thursday' => $mondayToThursday,
            'friday' => $friday,
            'saturday' => [],
            'sunday' => [],
        ];
    }
}
