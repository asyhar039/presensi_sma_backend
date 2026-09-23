<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PolygonPoints implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $points = array_values($value);

        if (count($points) < 3) {
            $fail('The :attribute must contain at least 3 points.');

            return;
        }

        $seen = [];

        foreach ($points as $point) {
            if (! is_array($point) || count($point) !== 2) {
                $fail('Each point must be a [latitude, longitude] pair.');

                return;
            }

            [$latitude, $longitude] = array_values($point);

            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                $fail('Each coordinate must be numeric.');

                return;
            }

            $latitude = (float) $latitude;
            $longitude = (float) $longitude;

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                $fail('Latitude must be between -90 and 90 and longitude between -180 and 180.');

                return;
            }

            $key = "{$latitude},{$longitude}";

            if (isset($seen[$key])) {
                $fail('The :attribute must not contain duplicates.');

                return;
            }

            $seen[$key] = true;
        }
    }
}
