<?php

namespace App\Services\Presence;

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Events\PresenceScanned;
use App\Models\ClassSchedule;
use App\Models\LeaveRequest;
use App\Models\PresenceRecord;
use App\Models\PresenceSession;
use App\Models\PublicHoliday;
use App\Models\SchoolZone;
use App\Models\User;
use App\Services\StudentSpace\StudentSpaceCache;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PresenceService
{
    public function activeYearId(): ?int
    {
        return StudentSpaceCache::activeYearId();
    }

    // All slot comparisons run in school time (WIB); never rely on the server default.
    private function now(): Carbon
    {
        return now('Asia/Jakarta');
    }

    // ponytail: one row per slot+date in DB (ACID, no double-start); counters cached for reads, DB snapshot on close.

    private function slotRange(ClassSchedule $schedule): array
    {
        $start = substr((string) $schedule->getRawOriginal('start_time'), 0, 8);
        $end = substr((string) $schedule->getRawOriginal('end_time'), 0, 8);

        return [$start, $end];
    }

    public function teacherNow(User $teacher, ?Carbon $at = null): ?array
    {
        $teacherId = $teacher->teacher?->id;
        if ($teacherId === null) {
            return null;
        }
        $at ??= $this->now();
        $yearId = $this->activeYearId();
        $schedule = ClassSchedule::query()->with(['classroom', 'teacher.subjects'])
            ->where('teacher_id', $teacherId)
            ->where('day', strtolower($at->format('l')))
            ->whereTime('start_time', '<=', $at->format('H:i:s'))
            ->whereTime('end_time', '>=', $at->format('H:i:s'))
            ->when($yearId !== null, fn ($q) => $q->whereHas('classroom', fn ($qq) => $qq->where('academic_year_id', $yearId)))
            ->orderBy('start_time')->first();
        if ($schedule === null) {
            return null;
        }

        return $this->stateFor($schedule, $at);
    }

    public function studentNow(User $user, ?Carbon $at = null): ?array
    {
        $student = $user->student;
        if ($student === null) {
            return null;
        }
        $at ??= $this->now();
        $yearId = $this->activeYearId();
        $classroomId = $student->classrooms()
            ->when($yearId, fn ($q) => $q->where('classrooms.academic_year_id', $yearId))
            ->value('classrooms.id');
        if ($classroomId === null) {
            return null;
        }
        $schedule = ClassSchedule::query()->with(['classroom', 'teacher.user'])
            ->where('classroom_id', $classroomId)
            ->where('day', strtolower($at->format('l')))
            ->whereTime('start_time', '<=', $at->format('H:i:s'))
            ->whereTime('end_time', '>=', $at->format('H:i:s'))
            ->orderBy('start_time')->first();
        if ($schedule === null) {
            return null;
        }

        return $this->stateFor($schedule, $at);
    }

    public function stateFor(ClassSchedule $schedule, ?Carbon $at = null): array
    {
        $at ??= $this->now();
        [$start, $end] = $this->slotRange($schedule);
        $date = $at->toDateString();
        $session = PresenceSession::query()
            ->where('class_schedule_id', $schedule->id)->where('date', $date)->first();

        $subjects = $schedule->teacher?->subjects
            ?->filter(fn ($s): bool => $s->pivot === null || $schedule->classroom === null || (int) $s->pivot->academic_year_id === (int) $schedule->classroom->academic_year_id)
            ->sortBy('name')->values()
            ->map(fn ($s): array => ['id' => $s->id, 'name' => $s->name])->all() ?? [];

        return [
            'schedule' => [
                'id' => $schedule->id,
                'classroom' => $schedule->classroom ? ['id' => $schedule->classroom->id, 'name' => $schedule->classroom->name] : null,
                'teacher' => $schedule->teacher?->user ? ['id' => $schedule->teacher->id, 'name' => $schedule->teacher->user->name] : null,
                'day' => $schedule->day instanceof \BackedEnum ? $schedule->day->value : (string) $schedule->day,
                'start_time' => substr($start, 0, 5),
                'end_time' => substr($end, 0, 5),
                'subjects' => $subjects,
            ],
            'is_within_time' => $at->format('H:i:s') >= $start && $at->format('H:i:s') <= $end,
            'has_session' => $session !== null,
            'is_started' => $session !== null,
            'is_stopped' => $session !== null && $session->status === 'closed',
            'qr_expired' => $session === null || $session->status !== 'open'
                || $session->qr_expires_at === null || $this->now()->greaterThan($session->qr_expires_at),
            'qr_value' => $session !== null && $session->status === 'open' ? $session->current_key : null,
            'qr_expires_at' => $session?->qr_expires_at?->toISOString(),
        ];
    }

    public function current(User $teacher, ?Carbon $at = null): array
    {
        $row = $this->teacherNow($teacher, $at) ?? ['schedule' => null, 'is_within_time' => false, 'has_session' => false, 'is_started' => false, 'is_stopped' => false, 'qr_expired' => true, 'qr_value' => null, 'qr_expires_at' => null];

        if ($row['schedule'] !== null && ($row['has_session'] ?? false)) {
            $schedule = ClassSchedule::query()->find($row['schedule']['id']);
            $session = PresenceSession::query()->where('class_schedule_id', $schedule->id)
                ->where('date', ($at ?? $this->now())->toDateString())->first();
            if ($session !== null) {
                $row['feed'] = $this->feed($session);
                $row['analytics'] = $this->analytics($session);
                $row['channel'] = "presence.session.{$session->id}";
                $row['session_id'] = $session->id;
            }
        }

        return $row;
    }

    public function rosterCount(int $classroomId): int
    {
        return (int) DB::table('student_classrooms')->where('classroom_id', $classroomId)->count();
    }

    public function start(User $teacher, ?Carbon $at = null): array
    {
        $at ??= $this->now();
        $schedule = $this->activeTeacherSchedule($teacher, $at);
        [$start, $end] = $this->slotRange($schedule);
        abort_unless($at->format('H:i:s') >= $start && $at->format('H:i:s') <= $end, 422, 'Outside the scheduled time range.');
        $date = $at->toDateString();

        $session = DB::transaction(function () use ($schedule, $date, $at): PresenceSession {
            $existing = PresenceSession::query()->where('class_schedule_id', $schedule->id)
                ->where('date', $date)->lockForUpdate()->first();
            if ($existing !== null) {
                abort(422, $existing->status === 'closed' ? 'Session already closed; cannot start again.' : 'Session already started.');
            }

            return PresenceSession::query()->create([
                'class_schedule_id' => $schedule->id,
                'classroom_id' => $schedule->classroom_id,
                'teacher_id' => $schedule->teacher_id,
                'date' => $date,
                'status' => 'open',
                'current_key' => $this->issueKey(),
                'qr_expires_at' => $at->copy()->addSeconds(PresenceCache::ROTATE_SECONDS),
                'started_at' => $at,
                'total_students' => $this->rosterCount($schedule->classroom_id),
            ]);
        });

        Cache::put(PresenceCache::qrKey($session->current_key), $session->id, PresenceCache::ROTATE_SECONDS);
        Cache::put(PresenceCache::sessionKey($session->id), $session->id, 86400);

        return $this->stateFor($schedule->refresh(), $at) + ['feed' => $this->feed($session), 'analytics' => $this->analytics($session),
            'channel' => "presence.session.{$session->id}", 'session_id' => $session->id];
    }

    public function stop(User $teacher, ?Carbon $at = null): array
    {
        $at ??= $this->now();
        $schedule = $this->activeTeacherSchedule($teacher, $at, false);
        $session = DB::transaction(function () use ($schedule, $at): PresenceSession {
            $locked = PresenceSession::query()->where('class_schedule_id', $schedule->id)
                ->where('date', $at->toDateString())->lockForUpdate()->firstOrFail();
            abort_if($locked->status === 'closed', 422, 'Session already closed.');
            $analytics = $this->analytics($locked);
            $locked->fill([
                'status' => 'closed', 'closed_at' => $at,
                'total_present' => $analytics['total_present'], 'total_sick' => $analytics['total_sick'],
                'total_permit' => $analytics['total_permit'],
            ])->save();

            return $locked;
        });

        PresenceCache::forgetSession($session->id, $session->current_key);

        return $this->stateFor($schedule->refresh(), $at) + ['analytics' => $this->analytics($session->refresh())];
    }

    public function refreshQr(User $teacher, ?Carbon $at = null): array
    {
        $at ??= $this->now();
        $schedule = $this->activeTeacherSchedule($teacher, $at, false);
        $session = PresenceSession::query()->where('class_schedule_id', $schedule->id)
            ->where('date', $at->toDateString())->firstOrFail();
        abort_if($session->status !== 'open', 422, 'Session is not open.');
        [$start, $end] = $this->slotRange($schedule);
        abort_unless($at->format('H:i:s') >= $start && $at->format('H:i:s') <= $end, 422, 'Outside the scheduled time range.');

        if ($session->qr_expires_at === null || $at->greaterThanOrEqualTo($session->qr_expires_at)) {
            $old = $session->current_key;
            DB::transaction(function () use ($session, $at): void {
                $locked = PresenceSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
                $locked->fill([
                    'previous_key' => $locked->current_key, 'current_key' => $this->issueKey(),
                    'qr_expires_at' => $at->copy()->addSeconds(PresenceCache::ROTATE_SECONDS),
                ])->save();
                $session->forceFill($locked->getAttributes())->syncOriginal();
            });
            if ($old !== null) {
                Cache::forget(PresenceCache::qrKey($old));
            }
            Cache::put(PresenceCache::qrKey($session->current_key), $session->id, PresenceCache::ROTATE_SECONDS);
        }

        return $this->stateFor($schedule->refresh(), $at);
    }

    public function scan(User $user, string $key, ?float $latitude, ?float $longitude, ?Carbon $at = null): array
    {
        $at ??= $this->now();
        $student = $user->student;
        abort_if($student === null, 404, 'Student profile not found.');
        $sessionId = Cache::get(PresenceCache::qrKey($key));
        $session = $sessionId !== null ? PresenceSession::query()->find($sessionId) : null;
        if ($session === null) {
            $session = PresenceSession::query()->where('current_key', $key)->orWhere('previous_key', $key)->first();
        }
        abort_if($session === null || $session->status !== 'open', 422, 'QR code is invalid or expired.');
        abort_if($session->current_key === $key && $session->qr_expires_at !== null && $at->greaterThan($session->qr_expires_at), 422, 'QR code is invalid or expired.');
        abort_if($session->current_key !== $key && $session->previous_key !== $key, 422, 'QR code is invalid or expired.');
        // ponytail: 60s grace on previous_key; tighten to 0s when scanners stay in sync.

        $schedule = $session->schedule()->with('classroom')->firstOrFail();
        [$start, $end] = $this->slotRange($schedule);
        abort_if($session->date->toDateString() !== $at->toDateString(), 422, 'Session is not for today.');
        abort_unless($at->format('H:i:s') >= $start && $at->format('H:i:s') <= $end, 422, 'Outside the scheduled time range.');
        $enrolled = DB::table('student_classrooms')->where('classroom_id', $session->classroom_id)->where('student_id', $student->id)->exists();
        abort_unless($enrolled, 403, 'You are not eligible for this session.');

        $inside = $this->insideZone($latitude, $longitude);

        try {
            $record = DB::transaction(function () use ($session, $student, $user, $at, $latitude, $longitude, $inside): PresenceRecord {
                $exists = PresenceRecord::query()->where('presence_session_id', $session->id)
                    ->where('student_id', $student->id)->lockForUpdate()->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['key' => 'You have already recorded presence for this session.']);
                }

                return PresenceRecord::query()->create([
                    'presence_session_id' => $session->id, 'student_id' => $student->id, 'user_id' => $user->id,
                    'scanned_at' => $at, 'latitude' => $latitude, 'longitude' => $longitude, 'inside_zone' => $inside,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['key' => 'You have already recorded presence for this session.']);
        }

        PresenceCache::increment($session->id, 'scans');
        $session->increment('total_present');

        $leaves = $this->leaveMap($session->refresh());
        $studentRow = [
            'name' => $user->name,
            'identity_number' => $user->identity_number,
            'scanned_at' => $record->scanned_at->toISOString(),
            'inside_zone' => $inside,
            'has_leave' => isset($leaves[$user->id]),
            'leave_type' => $leaves[$user->id]['type'] ?? null,
            'leave_time' => $leaves[$user->id]['time'] ?? null,
        ];
        PresenceScanned::dispatch($session->id, $studentRow, $this->analytics($session));

        return [
            'record_id' => $record->id, 'scanned_at' => $record->scanned_at->toISOString(),
            'inside_zone' => $inside, 'session_id' => $session->id,
        ];
    }

    public function feed(PresenceSession $session): array
    {
        $records = PresenceRecord::query()->with(['student.user'])
            ->where('presence_session_id', $session->id)->orderByDesc('scanned_at')->limit(50)->get();
        $leaves = $this->leaveMap($session);

        return $records->map(fn ($record): array => [
            'name' => $record->student?->user?->name,
            'identity_number' => $record->student?->user?->identity_number,
            'scanned_at' => $record->scanned_at?->toISOString(),
            'inside_zone' => (bool) $record->inside_zone,
            'has_leave' => isset($leaves[$record->user_id]),
            'leave_type' => $leaves[$record->user_id]['type'] ?? null,
            'leave_time' => $leaves[$record->user_id]['time'] ?? null,
        ])->all();
    }

    public function analytics(PresenceSession $session): array
    {
        $session->loadMissing('classroom');
        $totalStudents = $session->total_students > 0 ? $session->total_students : $this->rosterCount($session->classroom_id);
        $totalPresent = $session->status === 'closed' && $session->total_present > 0
            ? $session->total_present
            : PresenceRecord::query()->where('presence_session_id', $session->id)->count();
        $leaves = $this->leaveMap($session);
        $sick = collect($leaves)->where('type', LeaveRequestTypeEnum::SickLeave->value)->count();
        $permit = collect($leaves)->whereIn('type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value])->count();

        return [
            'total_students' => $totalStudents, 'total_present' => $totalPresent,
            'total_sick' => $sick, 'total_permit' => $permit,
            'total_early_out' => collect($leaves)->where('type', LeaveRequestTypeEnum::EarlyOut->value)->count(),
            'total_late_arrival' => collect($leaves)->where('type', LeaveRequestTypeEnum::LateArrival->value)->count(),
        ];
    }

    private function leaveMap(PresenceSession $session): array
    {
        $date = $session->date->toDateString();
        $userIds = DB::table('student_classrooms')
            ->join('students', 'students.id', '=', 'student_classrooms.student_id')
            ->where('student_classrooms.classroom_id', $session->classroom_id)
            ->pluck('students.user_id')->all();
        if ($userIds === []) {
            return [];
        }
        $leaves = LeaveRequest::query()
            ->whereIn('user_id', $userIds)->where('status', LeaveRequestStatusEnum::Approved->value)
            ->where(function ($q) use ($date): void {
                $q->where(function ($qq) use ($date): void {
                    $qq->where('type', LeaveRequestTypeEnum::SickLeave->value)
                        ->where('start_date', '<=', $date)->where('end_date', '>=', $date);
                })->orWhere(function ($qq) use ($date): void {
                    $qq->whereIn('type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value])
                        ->whereDate('date', $date);
                });
            })->get(['user_id', 'type', 'time_out', 'time_in', 'estimated_arrival_time']);

        $map = [];
        foreach ($leaves as $leave) {
            $type = $leave->type instanceof \BackedEnum ? $leave->type->value : (string) $leave->type;
            $time = match ($type) {
                LeaveRequestTypeEnum::EarlyOut->value => substr((string) $leave->getRawOriginal('time_out'), 0, 5) ?: null,
                LeaveRequestTypeEnum::LateArrival->value => substr((string) $leave->getRawOriginal('estimated_arrival_time'), 0, 5) ?: null,
                default => substr((string) $leave->getRawOriginal('time_in'), 0, 5) ?: null,
            };
            $map[$leave->user_id] = ['type' => $type, 'time' => $time];
        }

        return $map;
    }

    public function monthlyHistory(User $user, string $month): array
    {
        $student = $user->student;
        abort_if($student === null, 404, 'Student profile not found.');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $yearId = $this->activeYearId();
        $classroomId = $student->classrooms()
            ->when($yearId, fn ($q) => $q->where('classrooms.academic_year_id', $yearId))
            ->value('classrooms.id');

        $schedules = $classroomId !== null
            ? ClassSchedule::query()->with(['classroom', 'teacher.user'])
                ->where('classroom_id', $classroomId)->orderBy('start_time')->get()
            : collect();

        $sessions = PresenceSession::query()->with('schedule')
            ->whereIn('class_schedule_id', $schedules->pluck('id')->all())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn ($s): string => $s->date->toDateString().'#'.$s->class_schedule_id);

        $scanned = PresenceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]))
            ->pluck('presence_session_id')->flip()->all();

        $approvedSick = LeaveRequest::query()->where('user_id', $user->id)
            ->where('status', LeaveRequestStatusEnum::Approved->value)
            ->where('type', LeaveRequestTypeEnum::SickLeave->value)
            ->where('start_date', '<=', $end->toDateString())->where('end_date', '>=', $start->toDateString())
            ->get(['start_date', 'end_date', 'range_date'])->all();
        $sickDays = [];
        foreach ($approvedSick as $leave) {
            foreach ($leave->range_date ?? [] as $day) {
                $sickDays[$day] = true;
            }
            if (($leave->range_date ?? []) === []) {
                for ($d = Carbon::parse($leave->start_date); $d->lte(Carbon::parse($leave->end_date)); $d->addDay()) {
                    $sickDays[$d->toDateString()] = true;
                }
            }
        }
        $permits = LeaveRequest::query()->where('user_id', $user->id)
            ->where('status', LeaveRequestStatusEnum::Approved->value)
            ->whereIn('type', [LeaveRequestTypeEnum::EarlyOut->value, LeaveRequestTypeEnum::LateArrival->value])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'type', 'time_out', 'time_in', 'estimated_arrival_time'])
            ->groupBy(fn ($l): string => Carbon::parse($l->date)->toDateString())->all();

        $holidays = PublicHoliday::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip()->all();

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $dayName = strtolower($date->format('l'));
            $daySchedules = $schedules->filter(fn ($s): bool => (($s->day instanceof \BackedEnum) ? $s->day->value : (string) $s->day) === $dayName)->values()->all();
            $entries = [];
            foreach ($daySchedules as $schedule) {
                $day = $schedule->day instanceof \BackedEnum ? $schedule->day->value : (string) $schedule->day;
                $session = $sessions->get($key.'#'.$schedule->id);
                if (isset($holidays[$key])) {
                    $status = 'holiday';
                } elseif (isset($sickDays[$key])) {
                    $status = 'sick_leave';
                } elseif (isset($permits[$key])) {
                    $status = 'permit';
                } elseif ($session !== null && isset($scanned[$session->id])) {
                    $status = 'present';
                } elseif ($session !== null && $session->status === 'closed' && $key < $this->now()->toDateString()) {
                    $status = 'alpha';
                } elseif ($key < $this->now()->toDateString()) {
                    $status = 'alpha';
                } else {
                    $status = 'upcoming';
                }
                $entries[] = [
                    'schedule_id' => $schedule->id, 'day' => $day,
                    'start_time' => substr((string) $schedule->getRawOriginal('start_time'), 0, 5),
                    'end_time' => substr((string) $schedule->getRawOriginal('end_time'), 0, 5),
                    'teacher' => $schedule->teacher?->user?->name,
                    'status' => $status,
                    'permit_types' => isset($permits[$key])
                        ? collect($permits[$key])->map(fn ($l): string => $l->type instanceof \BackedEnum ? $l->type->value : (string) $l->type)->values()->all()
                        : [],
                ];
            }
            $days[] = ['date' => $key, 'day' => $dayName, 'is_holiday' => isset($holidays[$key]), 'schedules' => $entries];
        }

        return ['month' => $month, 'days' => $days];
    }

    private function activeTeacherSchedule(User $teacher, Carbon $at, bool $mustBeOpen = true): ClassSchedule
    {
        $teacherId = $teacher->teacher?->id;
        abort_if($teacherId === null, 404, 'Teacher profile not found.');
        $yearId = $this->activeYearId();
        $schedule = ClassSchedule::query()->with(['classroom', 'teacher.subjects'])
            ->where('teacher_id', $teacherId)
            ->where('day', strtolower($at->format('l')))
            ->whereTime('start_time', '<=', $at->format('H:i:s'))
            ->whereTime('end_time', '>=', $at->format('H:i:s'))
            ->when($yearId !== null, fn ($q) => $q->whereHas('classroom', fn ($qq) => $qq->where('academic_year_id', $yearId)))
            ->orderBy('start_time')->first();
        abort_if($schedule === null, 404, 'No active schedule right now.');
        if ($mustBeOpen) {
            [$start, $end] = $this->slotRange($schedule);
            abort_unless($at->format('H:i:s') >= $start && $at->format('H:i:s') <= $end, 422, 'Outside the scheduled time range.');
        }

        return $schedule;
    }

    private function issueKey(): string
    {
        do {
            $key = Str::random(32);
        } while (PresenceSession::query()->where('current_key', $key)->exists());

        return $key;
    }

    public function insideZone(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }
        $zones = SchoolZone::query()->active()->get(['points']);
        if ($zones->isEmpty()) {
            return true;
        }
        foreach ($zones as $zone) {
            if ($this->pointInPolygon($latitude, $longitude, $zone->points ?? [])) {
                return true;
            }
        }

        return false;
    }

    private function pointInPolygon(float $latitude, float $longitude, array $points): bool
    {
        $count = count($points);
        if ($count < 3) {
            return false;
        }
        $inside = false;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) ($points[$i][1] ?? $points[$i]['lng'] ?? 0);
            $yi = (float) ($points[$i][0] ?? $points[$i]['lat'] ?? 0);
            $xj = (float) ($points[$j][1] ?? $points[$j]['lng'] ?? 0);
            $yj = (float) ($points[$j][0] ?? $points[$j]['lat'] ?? 0);
            if (($yi > $latitude) !== ($yj > $latitude)
                && $longitude < ($xj - $xi) * ($latitude - $yi) / (($yj - $yi) ?: 1e-12) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
