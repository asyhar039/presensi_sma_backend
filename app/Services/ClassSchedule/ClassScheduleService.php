<?php

namespace App\Services\ClassSchedule;

use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Services\Setting\ScheduleSettingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{
    public function __construct(private ScheduleSettingService $schedules) {}

    /**
     * @return Collection<int, ClassSchedule>
     */
    public function list(int $classroomId, string $day): Collection
    {
        $academicYearId = Classroom::query()->whereKey($classroomId)->value('academic_year_id');

        return ClassSchedule::query()
            ->with(['classroom', 'teacher.user', 'teacher.subjects' => fn ($query) => $academicYearId
                ? $query->where('teacher_subjects.academic_year_id', $academicYearId)->orderBy('name')
                : $query->orderBy('name')])
            ->where('classroom_id', $classroomId)
            ->where('day', $day)
            ->orderBy('period')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  array{classroom_id: int, day: string, period: int, start_time: string, end_time: string, teacher_id?: int|null}  $data
     */
    public function create(array $data): void
    {
        $this->assertSlot($data['day'], $data['period'], $data['start_time'], $data['end_time']);
        $this->assertAvailable($data['classroom_id'], $data['day'], (int) $data['period'], $data['teacher_id'] ?? null, $data['start_time'], $data['end_time']);

        ClassSchedule::query()->create([
            'classroom_id' => $data['classroom_id'],
            'day' => $data['day'],
            'period' => $data['period'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'teacher_id' => $data['teacher_id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ClassSchedule $schedule, array $data): void
    {
        $day = (string) ($data['day'] ?? $schedule->day->value);
        $period = (int) ($data['period'] ?? $schedule->period);
        $start = (string) ($data['start_time'] ?? substr((string) $schedule->getRawOriginal('start_time'), 0, 5));
        $end = (string) ($data['end_time'] ?? substr((string) $schedule->getRawOriginal('end_time'), 0, 5));
        $teacherId = array_key_exists('teacher_id', $data) ? $data['teacher_id'] : $schedule->teacher_id;
        $classroomId = (int) ($data['classroom_id'] ?? $schedule->classroom_id);

        $this->assertSlot($day, $period, $start, $end);
        $this->assertAvailable($classroomId, $day, $period, $teacherId !== null ? (int) $teacherId : null, $start, $end, $schedule->id);

        $schedule->fill([
            'classroom_id' => $classroomId,
            'day' => $day,
            'period' => $period,
            'start_time' => $start,
            'end_time' => $end,
            'teacher_id' => $teacherId,
        ]);
        $schedule->save();
    }

    public function delete(ClassSchedule $schedule): void
    {
        $schedule->delete();
    }

    private function assertSlot(string $day, int $period, string $start, string $end): void
    {
        $slots = $this->schedules->getDay($day);

        if ($slots === []) {
            throw ValidationException::withMessages(['day' => 'The selected day is a holiday.']);
        }

        $lessons = array_values(array_filter($slots, fn (array $slot): bool => ! $slot['is_break']));
        $slot = $lessons[$period] ?? null;

        if ($slot === null) {
            throw ValidationException::withMessages(['period' => 'The selected period is not available on the selected day.']);
        }

        if ($start !== $slot['start'] || $end !== $slot['end']) {
            throw ValidationException::withMessages([
                'start_time' => "Start and end time must match the scheduled slot {$slot['start']}-{$slot['end']}.",
            ]);
        }
    }

    private function assertAvailable(int $classroomId, string $day, int $period, ?int $teacherId, string $start, string $end, ?int $exceptId = null): void
    {
        $taken = ClassSchedule::query()
            ->where('classroom_id', $classroomId)
            ->where('day', $day)
            ->where('period', $period)
            ->when($teacherId === null, fn ($query) => $query->whereNull('teacher_id'), fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['period' => 'The selected slot is already taken.']);
        }

        if ($teacherId === null) {
            return;
        }

        $busy = ClassSchedule::query()
            ->where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('start_time', '<', $this->toTime($end))
            ->where('end_time', '>', $this->toTime($start))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($busy) {
            throw ValidationException::withMessages(['teacher_id' => 'The teacher already has a schedule at that time.']);
        }
    }

    private function toTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
