<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateScheduleSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('schedules.days', [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ]);
    }
}
