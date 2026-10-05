<?php

namespace App\Services\StudentSpace;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\Cache;

final class StudentSpaceCache
{
    public const ACTIVE_YEAR_ID = 'student-space:active-year-id';

    public const UPCOMING_HOLIDAYS = 'student-space:upcoming-holidays';

    public static function classTimes(int $classroomId): string
    {
        return "student-space:class-times:{$classroomId}";
    }

    public static function activeYearId(): ?int
    {
        return Cache::rememberForever(self::ACTIVE_YEAR_ID, fn (): ?int => AcademicYear::query()->active()->value('id'));
    }

    public static function forgetActiveYear(): void
    {
        Cache::forget(self::ACTIVE_YEAR_ID);
    }

    public static function forgetUpcomingHolidays(): void
    {
        Cache::forget(self::UPCOMING_HOLIDAYS);
    }

    public static function forgetClassTimes(int $classroomId): void
    {
        Cache::forget(self::classTimes($classroomId));
    }
}
