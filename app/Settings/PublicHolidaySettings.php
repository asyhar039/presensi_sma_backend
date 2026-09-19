<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PublicHolidaySettings extends Settings
{
    public array $holidays;

    public static function group(): string
    {
        return 'public_holidays';
    }
}
