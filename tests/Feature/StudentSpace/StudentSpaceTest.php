<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function studentToken(): array
{
    $user = User::factory()->student()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);

    return [$user->createToken('t')->plainTextToken, $user, $student];
}

function schoolDay(): void
{
    app(ScheduleSettingService::class)->updateDay(strtolower(now()->format('l')), [
        ['start' => '07:00', 'end' => '07:40'],
        ['start' => '07:40', 'end' => '08:20'],
        ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
        ['start' => '08:40', 'end' => '09:20'],
        ['start' => '09:20', 'end' => '10:00'],
    ]);
}

test('student information returns academic year and class', function (): void {
    [$token, $user, $student] = studentToken();
    $year = AcademicYear::factory()->active()->create([
        'odd_start_date' => '2026-07-01', 'odd_end_date' => '2026-12-20',
        'even_start_date' => '2027-01-05', 'even_end_date' => '2027-06-20',
    ]);
    $tUser = User::factory()->teacher()->create(['name' => 'Wali', 'email' => 'wali@x.id']);
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'homeroom_teacher_id' => $teacher->id, 'name' => 'XII F 3']);
    $classroom->students()->sync([$student->id]);

    $this->withToken($token)->getJson('/student/information')->assertOk()
        ->assertJsonPath('data.academic_year.id', $year->id)
        ->assertJsonPath('data.class.name', 'XII F 3')
        ->assertJsonPath('data.class.homeroom_teacher.name', 'Wali');
});

test('student information empty when no active year', function (): void {
    [$token] = studentToken();

    $this->withToken($token)->getJson('/student/information')->assertOk()
        ->assertJsonPath('data.academic_year', null)
        ->assertJsonPath('data.class', null);
});

test('presence returns times holidays and flags', function (): void {
    [$token, $user, $student] = studentToken();
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
    $classroom->students()->sync([$student->id]);
    $day = strtolower(now()->format('l'));
    schoolDay();
    ClassSchedule::create(['classroom_id' => $classroom->id, 'day' => $day, 'period' => 0, 'start_time' => '07:00', 'end_time' => '07:40']);
    PublicHoliday::factory()->create(['name' => 'Libur', 'date' => now()->addDays(5)->toDateString()]);

    $this->withToken($token)->getJson('/student/presence')->assertOk()
        ->assertJsonPath("data.time.{$day}.start_time", '07:00')
        ->assertJsonPath('data.has_early_out', false)
        ->assertJsonCount(1, 'data.public_holidays');
});

test('non-students forbidden', function (): void {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/student/information')->assertForbidden();
    $this->withToken($token)->postJson('/student/leave-requests/sick-leave', [])->assertForbidden();
});

test('sick leave computes range skipping non-school days', function (): void {
    [$token] = studentToken();
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $d) {
        app(ScheduleSettingService::class)->updateDay($d, [
            ['start' => '07:00', 'end' => '07:40'], ['start' => '07:40', 'end' => '08:20'],
            ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
            ['start' => '08:40', 'end' => '09:20'], ['start' => '09:20', 'end' => '10:00'],
        ]);
    }
    // next Monday..Friday
    $monday = now()->next('Monday')->toDateString();
    $friday = Carbon::parse($monday)->addDays(4)->toDateString();

    $this->withToken($token)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => $monday, 'end_date' => $friday, 'notes' => 'sakit',
    ])->assertCreated()->assertJsonPath('data.range_date.0', $monday);

    // weekend-only range errors (sat-sun are holidays by default seed)
    $sat = now()->next('Saturday')->toDateString();
    $sun = Carbon::parse($sat)->addDay()->toDateString();
    $this->withToken($token)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => $sat, 'end_date' => $sun,
    ])->assertUnprocessable();
});

test('early out and late arrival enforce school day and one per day', function (): void {
    [$token] = studentToken();
    schoolDay();

    $this->withToken($token)->postJson('/student/leave-requests/early-out', ['time_out' => '10:00', 'time_in' => '12:00',
        'exit_reason' => 'pergi', 'destination' => 'rumah',
    ])->assertCreated()->assertJsonPath('data.type.key', 'early_out');

    $this->withToken($token)->postJson('/student/leave-requests/early-out', ['time_out' => '10:00', 'time_in' => '12:00',
        'exit_reason' => 'x', 'destination' => 'y',
    ])->assertUnprocessable();

    $this->withToken($token)->postJson('/student/leave-requests/late-arrival', ['estimated_arrival_time' => '09:00', 'late_reason' => 'macet',
    ])->assertCreated();

    $this->withToken($token)->getJson('/student/presence')->assertOk()
        ->assertJsonPath('data.has_early_out', true)
        ->assertJsonPath('data.has_late_arrival', true);
});

test('holiday blocks early out', function (): void {
    [$token] = studentToken();
    app(ScheduleSettingService::class)->updateDay(strtolower(now()->format('l')), []);
    $this->withToken($token)->postJson('/student/leave-requests/late-arrival', ['estimated_arrival_time' => '09:00', 'late_reason' => 'x',
    ])->assertUnprocessable();
});

test('key auto-generated unique and attachment validated', function (): void {
    [$token] = studentToken();
    schoolDay();
    Storage::fake('public');
    $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $res = $this->withToken($token)->postJson('/student/leave-requests/late-arrival', ['estimated_arrival_time' => '09:00', 'late_reason' => 'x',
        'attachment' => $pdf,
    ])->assertCreated();
    expect(strlen($res->json('data.key')))->toBe(16);

    $exe = UploadedFile::fake()->create('evil.exe', 100, 'application/octet-stream');
    $this->withToken($token)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => now()->toDateString(), 'end_date' => now()->addWeek()->toDateString(),
        'attachment' => $exe,
    ])->assertUnprocessable();
});

test('leave list uses datatable and detail enforces ownership', function (): void {
    [$token, $user] = studentToken();
    schoolDay();
    $monday = now()->next('Monday')->toDateString();
    $friday = Carbon::parse($monday)->addDays(4)->toDateString();
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $d) {
        app(ScheduleSettingService::class)->updateDay($d, [
            ['start' => '07:00', 'end' => '07:40'], ['start' => '07:40', 'end' => '08:20'],
            ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
            ['start' => '08:40', 'end' => '09:20'], ['start' => '09:20', 'end' => '10:00'],
        ]);
    }

    $this->withToken($token)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => $monday, 'end_date' => $friday,
    ])->assertCreated();
    $this->withToken($token)->postJson('/student/leave-requests/early-out', [
        'time_out' => '10:00', 'time_in' => '12:00', 'exit_reason' => 'pergi', 'destination' => 'rumah',
    ])->assertCreated();

    $this->withToken($token)->getJson('/student/leave-requests')->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure(['data' => [['id', 'type', 'status', 'key', 'range_date', 'date']]]);

    $this->withToken($token)->getJson('/student/leave-requests?type=sick_leave')->assertOk()
        ->assertJsonPath('meta.total', 1);

    $id = LeaveRequest::query()->where('user_id', $user->id)->latest('id')->value('id');
    $this->withToken($token)->getJson("/student/leave-requests/{$id}")->assertOk()
        ->assertJsonPath('data.id', $id);

    [$otherToken] = studentToken();
    $otherUser = User::query()->whereKeyNot($user->id)->latest('id')->first();
    $this->actingAs($otherUser)->getJson("/student/leave-requests/{$id}")->assertNotFound();
    $this->withToken($token)->getJson('/student/leave-requests/999999')->assertNotFound();
});
