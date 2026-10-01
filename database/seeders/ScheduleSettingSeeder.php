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

        $service->updateDay('saturday', []);
        $service->updateDay('sunday', []);
    }

    /**
     * @return array<string, list<array{start: string, end: string, is_break: bool}>>
     */
    private function days(): array
    {
        return [
            'monday' => [
                ['start' => '07:00', 'end' => '07:45', 'is_break' => false],
                ['start' => '07:45', 'end' => '08:30', 'is_break' => false],
                ['start' => '08:30', 'end' => '09:15', 'is_break' => false],
                ['start' => '09:15', 'end' => '09:30', 'is_break' => false],
                ['start' => '09:30', 'end' => '09:45', 'is_break' => true],
                ['start' => '09:45', 'end' => '10:15', 'is_break' => false],
                ['start' => '10:15', 'end' => '11:00', 'is_break' => false],
                ['start' => '11:00', 'end' => '11:45', 'is_break' => false],
                ['start' => '11:45', 'end' => '12:00', 'is_break' => false],
                ['start' => '12:00', 'end' => '12:30', 'is_break' => true],
                ['start' => '12:30', 'end' => '13:00', 'is_break' => false],
                ['start' => '13:00', 'end' => '13:45', 'is_break' => false],
                ['start' => '13:45', 'end' => '14:30', 'is_break' => false],
                ['start' => '14:30', 'end' => '15:15', 'is_break' => false],
                ['start' => '15:15', 'end' => '16:00', 'is_break' => false],
            ],
            'tuesday' => [
                ['start' => '07:00', 'end' => '07:45', 'is_break' => false],
                ['start' => '07:45', 'end' => '08:30', 'is_break' => false],
                ['start' => '08:30', 'end' => '09:15', 'is_break' => false],
                ['start' => '09:15', 'end' => '09:30', 'is_break' => false],
                ['start' => '09:30', 'end' => '09:45', 'is_break' => true],
                ['start' => '09:45', 'end' => '10:15', 'is_break' => false],
                ['start' => '10:15', 'end' => '11:00', 'is_break' => false],
                ['start' => '11:00', 'end' => '11:45', 'is_break' => false],
                ['start' => '11:45', 'end' => '12:00', 'is_break' => false],
                ['start' => '12:00', 'end' => '12:30', 'is_break' => true],
                ['start' => '12:30', 'end' => '13:00', 'is_break' => false],
                ['start' => '13:00', 'end' => '13:45', 'is_break' => false],
                ['start' => '13:45', 'end' => '14:30', 'is_break' => false],
                ['start' => '14:30', 'end' => '15:15', 'is_break' => false],
                ['start' => '15:15', 'end' => '16:00', 'is_break' => false],
            ],
            'wednesday' => [
                ['start' => '07:00', 'end' => '07:45', 'is_break' => false],
                ['start' => '07:45', 'end' => '08:30', 'is_break' => false],
                ['start' => '08:30', 'end' => '09:15', 'is_break' => false],
                ['start' => '09:15', 'end' => '09:30', 'is_break' => false],
                ['start' => '09:30', 'end' => '09:45', 'is_break' => true],
                ['start' => '09:45', 'end' => '10:15', 'is_break' => false],
                ['start' => '10:15', 'end' => '11:00', 'is_break' => false],
                ['start' => '11:00', 'end' => '11:45', 'is_break' => false],
                ['start' => '11:45', 'end' => '12:00', 'is_break' => false],
                ['start' => '12:00', 'end' => '12:30', 'is_break' => true],
                ['start' => '12:30', 'end' => '13:00', 'is_break' => false],
                ['start' => '13:00', 'end' => '13:45', 'is_break' => false],
                ['start' => '13:45', 'end' => '14:30', 'is_break' => false],
                ['start' => '14:30', 'end' => '15:15', 'is_break' => false],
            ],
            'thursday' => [
                ['start' => '07:00', 'end' => '07:45', 'is_break' => false],
                ['start' => '07:45', 'end' => '08:30', 'is_break' => false],
                ['start' => '08:30', 'end' => '09:15', 'is_break' => false],
                ['start' => '09:15', 'end' => '09:30', 'is_break' => false],
                ['start' => '09:30', 'end' => '09:45', 'is_break' => true],
                ['start' => '09:45', 'end' => '10:15', 'is_break' => false],
                ['start' => '10:15', 'end' => '11:00', 'is_break' => false],
                ['start' => '11:00', 'end' => '11:45', 'is_break' => false],
                ['start' => '11:45', 'end' => '12:00', 'is_break' => false],
                ['start' => '12:00', 'end' => '12:30', 'is_break' => true],
                ['start' => '12:30', 'end' => '13:00', 'is_break' => false],
                ['start' => '13:00', 'end' => '13:45', 'is_break' => false],
                ['start' => '13:45', 'end' => '14:30', 'is_break' => false],
                ['start' => '14:30', 'end' => '15:15', 'is_break' => false],
            ],
            'friday' => [
                ['start' => '07:00', 'end' => '07:45', 'is_break' => false],
                ['start' => '07:45', 'end' => '08:30', 'is_break' => false],
                ['start' => '08:30', 'end' => '09:15', 'is_break' => false],
                ['start' => '09:15', 'end' => '10:00', 'is_break' => false],
                ['start' => '10:00', 'end' => '10:45', 'is_break' => false],
                ['start' => '10:45', 'end' => '11:30', 'is_break' => false],
                ['start' => '11:30', 'end' => '12:30', 'is_break' => true],
                ['start' => '12:30', 'end' => '13:15', 'is_break' => false],
            ],
        ];
    }
}
