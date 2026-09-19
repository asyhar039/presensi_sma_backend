<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ScheduleSettings extends Settings
{
    public array $days;

    public static function group(): string
    {
        return 'schedules';
    }
}
