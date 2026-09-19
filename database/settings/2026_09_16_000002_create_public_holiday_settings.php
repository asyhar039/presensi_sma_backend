<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePublicHolidaySettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('public_holidays.holidays', []);
    }
}
