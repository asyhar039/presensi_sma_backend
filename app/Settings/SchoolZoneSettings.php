<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SchoolZoneSettings extends Settings
{
    public array $zones;

    public static function group(): string
    {
        return 'school_zones';
    }
}
