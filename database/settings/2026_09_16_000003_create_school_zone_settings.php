<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateSchoolZoneSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('school_zones.zones', []);
    }
}
