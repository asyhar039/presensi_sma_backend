<?php

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Events\PresenceScanned;
use App\Models\LeaveRequest;
use App\Models\PresenceSession;
use App\Models\PublicHoliday;
use App\Models\SchoolZone;
use App\Services\Presence\PresenceCache;
use Carbon\Carbon;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

uses()->afterEach(function (): void {
    Carbon::setTestNow();
});

test('autodetect finds wednesday 08:59 wib inside 07:30-09:30 slot', function (): void {
    $at = Carbon::parse('2026-10-07 08:59:00', 'Asia/Jakarta');
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule] = presenceSetupHelper($at);
    $schedule->update(['start_time' => '07:30:00', 'end_time' => '09:30:00']);
    expect(strtolower($at->format('l')))->toBe('wednesday');

    $token = $tUser->createToken('w')->plainTextToken;
    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/presence/current')->assertOk()
        ->assertJsonPath('data.schedule.id', $schedule->id)
        ->assertJsonPath('data.is_within_time', true)
        ->assertJsonPath('data.schedule.start_time', '07:30')
        ->assertJsonPath('data.schedule.end_time', '09:30');
});

test('teacher current shows schedule and no session', function (): void {
    [$tUser] = presenceSetupHelper();
    $token = $tUser->createToken('t')->plainTextToken;

    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/presence/current')->assertOk()
        ->assertJsonPath('data.is_within_time', true)
        ->assertJsonPath('data.is_started', false)
        ->assertJsonPath('data.qr_value', null);
});

test('start stop blocks restart and scans once', function (): void {
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule] = presenceSetupHelper();
    $tToken = $tUser->createToken('t')->plainTextToken;
    $sToken = $sUser->createToken('s')->plainTextToken;

    app('auth')->forgetGuards();
    $key = $this->withToken($tToken)->postJson('/presence/start')
        ->assertCreated()->assertJsonPath('data.is_started', true)->json('data.qr_value');
    expect($key)->toHaveLength(32);

    app('auth')->forgetGuards();
    $this->withToken($tToken)->postJson('/presence/start')->assertUnprocessable();

    SchoolZone::factory()->create(['points' => [[-6.2, 106.7], [-6.2, 106.9], [-6.0, 106.9], [-6.0, 106.7]]]);
    app('auth')->forgetGuards();
    $this->withToken($sToken)->postJson('/student/presence/scan', [
        'key' => $key, 'latitude' => -6.1, 'longitude' => 106.8,
    ])->assertCreated()->assertJsonPath('data.inside_zone', true);
    app('auth')->forgetGuards();
    $this->withToken($sToken)->postJson('/student/presence/scan', [
        'key' => $key, 'latitude' => -6.1, 'longitude' => 106.8,
    ])->assertUnprocessable();

    app('auth')->forgetGuards();
    $this->withToken($tToken)->getJson('/presence/current')->assertOk()
        ->assertJsonPath('data.analytics.total_present', 1)
        ->assertJsonCount(1, 'data.feed');

    app('auth')->forgetGuards();
    $this->withToken($tToken)->postJson('/presence/stop')->assertOk()
        ->assertJsonPath('data.is_stopped', true);
    app('auth')->forgetGuards();
    $this->withToken($tToken)->postJson('/presence/start')->assertUnprocessable();
});

test('scan broadcasts feed update on session channel', function (): void {
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule] = presenceSetupHelper();
    app('auth')->forgetGuards();
    $key = $this->withToken($tUser->createToken('b1')->plainTextToken)
        ->postJson('/presence/start')->assertCreated()->json('data.qr_value');
    app('auth')->forgetGuards();
    $sessionId = $this->withToken($tUser->createToken('b2')->plainTextToken)
        ->getJson('/presence/current')->assertOk()->json('data.session_id');

    $seen = [];
    Event::listen(PresenceScanned::class, function ($e) use (&$seen): void {
        $seen[] = $e;
    });
    app('auth')->forgetGuards();
    $this->withToken($sUser->createToken('b3')->plainTextToken)->postJson('/student/presence/scan', [
        'key' => $key, 'latitude' => -6.1, 'longitude' => 106.8,
    ])->assertCreated();

    expect($seen)->toHaveCount(1);
    expect($seen[0]->sessionId)->toBe($sessionId);
    expect($seen[0]->broadcastOn()[0]->name)->toBe("private-presence.session.{$sessionId}");
    expect($seen[0]->student['identity_number'])->toBe($sUser->identity_number);
    expect($seen[0]->analytics['total_present'])->toBe(1);
});

test('qr rotates after expiry', function (): void {
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule, $at] = presenceSetupHelper();
    $tToken = $tUser->createToken('t')->plainTextToken;
    app('auth')->forgetGuards();
    $key = $this->withToken($tToken)->postJson('/presence/start')
        ->assertCreated()->json('data.qr_value');

    Carbon::setTestNow($at->copy()->addSeconds(PresenceCache::ROTATE_SECONDS + 60));
    app('auth')->forgetGuards();
    $new = $this->withToken($tToken)->postJson('/presence/refresh')
        ->assertOk()->json('data.qr_value');
    expect($new)->not()->toBe($key);
});

test('history marks present alpha sick and holiday', function (): void {
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule, $at] = presenceSetupHelper();
    $sToken = $sUser->createToken('s')->plainTextToken;
    $session = PresenceSession::create([
        'class_schedule_id' => $schedule->id, 'classroom_id' => $classroom->id, 'teacher_id' => $teacher->id,
        'date' => '2026-10-06', 'status' => 'closed', 'started_at' => $at, 'closed_at' => $at, 'total_students' => 1,
    ]);
    $session->records()->create([
        'student_id' => $student->id, 'user_id' => $sUser->id, 'scanned_at' => $at, 'inside_zone' => true,
    ]);
    LeaveRequest::factory()->create([
        'user_id' => $sUser->id, 'type' => LeaveRequestTypeEnum::SickLeave, 'status' => LeaveRequestStatusEnum::Approved,
        'start_date' => '2026-10-13', 'end_date' => '2026-10-13', 'range_date' => ['2026-10-13'],
    ]);
    PublicHoliday::factory()->create(['date' => '2026-10-20']);

    app('auth')->forgetGuards();
    $days = $this->withToken($sToken)->getJson('/student/presence/history?month=2026-10')->assertOk()->json('data.days');
    $byDate = collect($days)->keyBy('date');
    expect($byDate['2026-10-06']['schedules'][0]['status'])->toBe('present');
    expect($byDate['2026-10-13']['schedules'][0]['status'])->toBe('sick_leave');
    expect($byDate['2026-10-20']['schedules'][0]['status'])->toBe('holiday');
});

test('channel allows session teacher, denies students', function (): void {
    [$tUser, $teacher, $sUser, $student, $classroom, $schedule] = presenceSetupHelper();
    $session = PresenceSession::create([
        'class_schedule_id' => $schedule->id, 'classroom_id' => $classroom->id, 'teacher_id' => $teacher->id,
        'date' => '2026-10-06', 'status' => 'open', 'started_at' => now(), 'current_key' => str_repeat('k', 32),
    ]);
    $broadcaster = app(Broadcaster::class);
    $prop = new ReflectionProperty($broadcaster, 'channels');
    $prop->setAccessible(true);
    $callbacks = $prop->getValue($broadcaster);
    $key = collect(array_keys($callbacks))->first(fn ($k): bool => str_contains($k, 'presence.session'));
    expect($key)->not()->toBeNull();
    $callback = $callbacks[$key];
    expect($callback($tUser->fresh()->load('teacher'), $session->id))->toBeTrue();
    expect($callback($sUser->fresh()->load('student'), $session->id))->toBeFalse();
});

test('role gates hold', function (): void {
    [$tUser, $teacher, $sUser] = presenceSetupHelper();
    $sToken = $sUser->createToken('s')->plainTextToken;
    $tToken = $tUser->createToken('t')->plainTextToken;

    app('auth')->forgetGuards();
    $this->withToken($sToken)->postJson('/presence/start')->assertForbidden();
    app('auth')->forgetGuards();
    $this->withToken($tToken)->postJson('/student/presence/scan', ['key' => str_repeat('a', 32)])->assertForbidden();
});
