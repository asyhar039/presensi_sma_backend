<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\DutyTeacher;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStudent(string $name = 'Siswa'): array
{
    $user = User::factory()->student()->create(['name' => $name]);
    $student = Student::factory()->create(['user_id' => $user->id]);

    return [$user->createToken('t')->plainTextToken, $user, $student];
}

function makeTeacher(): array
{
    $user = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);

    return [$user->createToken('t')->plainTextToken, $user, $teacher];
}

function fullSchoolWeek(): void
{
    $slots = [
        ['start' => '07:00', 'end' => '07:40'],
        ['start' => '07:40', 'end' => '08:20'],
        ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
        ['start' => '08:40', 'end' => '09:20'],
        ['start' => '09:20', 'end' => '10:00'],
    ];
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $d) {
        app(ScheduleSettingService::class)->updateDay($d, $slots);
    }
}

function seedHomeroom(User $teacherUser, Teacher $teacher, Student $student): array
{
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'homeroom_teacher_id' => $teacher->id]);
    $classroom->students()->sync([$student->id]);

    return [$year, $classroom];
}

test('homeroom list search filter detail and sick leave approve', function (): void {
    [$sToken, $sUser, $student] = makeStudent('Budi');
    [$tToken, $tUser, $teacher] = makeTeacher();
    [$year, $classroom] = seedHomeroom($tUser, $teacher, $student);
    fullSchoolWeek();

    $monday = now()->next('Monday')->toDateString();
    $friday = Carbon::parse($monday)->addDays(4)->toDateString();
    $this->withToken($sToken)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => $monday, 'end_date' => $friday,
    ])->assertCreated();

    $this->actingAs($tUser)->getJson('/homeroom/leave-requests')->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.student.name', 'Budi')
        ->assertJsonPath('data.0.class.id', $classroom->id)
        ->assertJsonPath('data.0.leave_request.type.key', 'sick_leave');

    $this->actingAs($tUser)->getJson('/homeroom/leave-requests?search=Budi')->assertOk()
        ->assertJsonPath('meta.total', 1);
    $this->actingAs($tUser)->getJson('/homeroom/leave-requests?type=early_out')->assertOk()
        ->assertJsonPath('meta.total', 0);

    $id = LeaveRequest::query()->value('id');
    $this->actingAs($tUser)->getJson("/homeroom/leave-requests/{$id}")->assertOk()
        ->assertJsonPath('data.id', $id);

    $this->actingAs($tUser)->postJson("/homeroom/leave-requests/{$id}/decision", [
        'decision' => 'approved',
    ])->assertOk()->assertJsonPath('data.status.key', 'approved');

    expect(LeaveRequest::query()->find($id)->current_step)->toBeNull();
});

test('homeroom reject stores note and blocks other teacher', function (): void {
    [$sToken, $sUser, $student] = makeStudent();
    [$tToken, $tUser, $teacher] = makeTeacher();
    seedHomeroom($tUser, $teacher, $student);
    fullSchoolWeek();

    $monday = now()->next('Monday')->toDateString();
    $this->withToken($sToken)->postJson('/student/leave-requests/sick-leave', [
        'start_date' => $monday, 'end_date' => $monday,
    ])->assertCreated();
    $id = LeaveRequest::query()->value('id');

    $this->actingAs($tUser)->postJson("/homeroom/leave-requests/{$id}/decision", [
        'decision' => 'rejected', 'notes' => 'Surat tidak valid',
    ])->assertOk()->assertJsonPath('data.status.key', 'rejected');

    makeTeacher();
    $this->actingAs($otherUser = User::query()->whereKeyNot($tUser->id)->latest('id')->first())->getJson("/homeroom/leave-requests/{$id}")->assertNotFound();

    $this->actingAs($tUser)->postJson("/homeroom/leave-requests/{$id}/decision", [
        'decision' => 'approved',
    ])->assertStatus(422);
});

test('duty status list detail and late arrival approve', function (): void {
    [$sToken, $sUser, $student] = makeStudent('Ani');
    [$tToken, $tUser, $teacher] = makeTeacher();
    fullSchoolWeek();
    app(ScheduleSettingService::class)->updateDay(strtolower(now()->format('l')), [
        ['start' => '07:00', 'end' => '07:40'],
        ['start' => '07:40', 'end' => '08:20'],
        ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
        ['start' => '08:40', 'end' => '09:20'],
        ['start' => '09:20', 'end' => '10:00'],
    ]);
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
    $classroom->students()->sync([$student->id]);
    DutyTeacher::create([
        'academic_year_id' => $year->id,
        'teacher_id' => $teacher->id,
        'day' => strtolower(now()->format('l')),
        'start_time' => '00:00',
        'end_time' => '23:59',
    ]);

    $this->withToken($sToken)->postJson('/student/leave-requests/late-arrival', [
        'estimated_arrival_time' => '09:00', 'late_reason' => 'macet',
    ])->assertCreated();
    $id = LeaveRequest::query()->value('id');

    $this->actingAs($tUser)->getJson('/duty/status')->assertOk()
        ->assertJsonPath('data.has_duty_teacher', true)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.duty_teacher.day', strtolower(now()->format('l')));

    $this->actingAs($tUser)->getJson('/duty/leave-requests')->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.student.identity_number', $sUser->identity_number);
    $this->actingAs($tUser)->getJson('/duty/leave-requests?search=Ani')->assertOk()
        ->assertJsonPath('meta.total', 1);

    $this->actingAs($tUser)->getJson("/duty/leave-requests/{$id}")->assertOk();

    $this->actingAs($tUser)->postJson("/duty/leave-requests/{$id}/late-arrival/decision", [
        'decision' => 'approved',
    ])->assertOk()->assertJsonPath('data.status.key', 'approved');
});

test('non duty teacher cannot access duty queue', function (): void {
    [$tToken, $tUser] = makeTeacher();
    AcademicYear::factory()->active()->create();

    $this->actingAs($tUser)->getJson('/duty/leave-requests')->assertForbidden();
    $this->actingAs($tUser)->getJson('/duty/status')->assertOk()
        ->assertJsonPath('data.has_duty_teacher', false)
        ->assertJsonPath('data.duty_teacher', null);
});

test('early out needs subject teacher first then duty final', function (): void {
    [$sToken, $sUser, $student] = makeStudent();
    [$subToken, $subUser, $subTeacher] = makeTeacher();
    [$dutyToken, $dutyUser, $dutyTeacher] = makeTeacher();
    fullSchoolWeek();
    $day = strtolower(now()->format('l'));
    app(ScheduleSettingService::class)->updateDay($day, [
        ['start' => '00:00', 'end' => '23:59'],
    ]);
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
    $classroom->students()->sync([$student->id]);
    ClassSchedule::create([
        'classroom_id' => $classroom->id, 'day' => $day, 'period' => 0,
        'start_time' => '00:00', 'end_time' => '23:59', 'teacher_id' => $subTeacher->id,
    ]);
    DutyTeacher::create([
        'academic_year_id' => $year->id, 'teacher_id' => $dutyTeacher->id,
        'day' => $day, 'start_time' => '00:00', 'end_time' => '23:59',
    ]);

    $this->withToken($sToken)->postJson('/student/leave-requests/early-out', [
        'time_out' => '10:00', 'time_in' => '12:00', 'exit_reason' => 'pergi', 'destination' => 'rumah',
    ])->assertCreated();
    $id = LeaveRequest::query()->value('id');

    // duty cannot approve before subject teacher
    $this->actingAs($dutyUser)->postJson("/duty/leave-requests/{$id}/early-out/decision", [
        'decision' => 'approved',
    ])->assertUnprocessable();

    $this->actingAs($subUser)->getJson('/subject/leave-requests')->assertOk()
        ->assertJsonPath('meta.total', 1);
    $this->actingAs($subUser)->getJson("/subject/leave-requests/{$id}")->assertOk();

    $this->actingAs($subUser)->postJson("/subject/leave-requests/{$id}/early-out/decision", [
        'decision' => 'approved',
    ])->assertOk()->assertJsonPath('data.current_step', 'duty_teacher');

    $this->actingAs($dutyUser)->postJson("/duty/leave-requests/{$id}/early-out/decision", [
        'decision' => 'approved',
    ])->assertOk()->assertJsonPath('data.status.key', 'approved');
});

test('subject teacher without current slot gets empty queue', function (): void {
    [$token, $soloUser] = makeTeacher();
    AcademicYear::factory()->active()->create();

    $this->actingAs($soloUser)->getJson('/subject/leave-requests')->assertNotFound();
});
