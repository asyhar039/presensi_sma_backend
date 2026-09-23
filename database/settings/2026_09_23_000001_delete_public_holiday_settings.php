<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class DeletePublicHolidaySettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('public_holidays.holidays');
    }
}
