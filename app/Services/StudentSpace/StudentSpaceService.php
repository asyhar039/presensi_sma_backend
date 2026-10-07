<?php

namespace App\Services\StudentSpace;

use App\Enums\DayEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentSpaceService
{
    public function __construct(private ScheduleSettingService $schedules) {}

    public function activeYear(): ?AcademicYear
    {
        $id = StudentSpaceCache::activeYearId();

        return $id === null ? null : AcademicYear::query()->find($id);
    }

    public function resolveClassroom(User $user, ?AcademicYear $year = null): ?Classroom
    {
        $year ??= $this->activeYear();

        if ($year === null || $user->student === null) {
            return null;
        }

        return $user->student->classrooms()
            ->where('classrooms.academic_year_id', $year->id)
            ->with('homeroomTeacher.user')
            ->first();
    }

    /**
     * @return array<string, array{start_time: string, end_time: string}|null>
     */
    public function dailyTimes(?Classroom $classroom): array
    {
        $times = array_fill_keys(DayEnum::values(), null);

        if ($classroom === null) {
            return $times;
        }

        $rows = ClassSchedule::query()
            ->where('classroom_id', $classroom->id)
            ->selectRaw('day, MIN(start_time) as start_time, MAX(end_time) as end_time')
            ->groupBy('day')
            ->get();

        foreach ($rows as $row) {
            $day = $row->day instanceof DayEnum ? $row->day->value : (string) $row->day;
            $times[$day] = [
                'start_time' => substr((string) $row->start_time, 0, 5),
                'end_time' => substr((string) $row->end_time, 0, 5),
            ];
        }

        return $times;
    }

    /**
     * @return Collection<int, PublicHoliday>
     */
    public function upcomingHolidays(): Collection
    {
        $today = Carbon::today();

        return PublicHoliday::query()
            ->whereDate('date', '>=', $today->toDateString())
            ->whereDate('date', '<=', $today->copy()->addMonth()->toDateString())
            ->ordered()
            ->get();
    }

    public function isSchoolDay(Carbon $date): bool
    {
        if ($this->schedules->getDay(strtolower($date->format('l'))) === []) {
            return false;
        }

        return ! PublicHoliday::query()->whereDate('date', $date->toDateString())->exists();
    }

    /**
     * @return list<string>
     */
    public function schoolDaysBetween(Carbon $start, Carbon $end): array
    {
        $holidays = PublicHoliday::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip()
            ->all();

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->schedules->getDay(strtolower($date->format('l'))) === []) {
                continue;
            }

            if (isset($holidays[$date->toDateString()])) {
                continue;
            }

            $days[] = $date->toDateString();
        }

        return $days;
    }
}
