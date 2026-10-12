<?php

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\LeaveRequest;
use App\Models\PresenceSession;
use App\Models\PublicHoliday;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->afterEach(fn () => Carbon::setTestNow());

function historySetup(): array
{
    Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00'));
    $year = AcademicYear::factory()->active()->create([
        'odd_start_date' => '2026-07-01', 'odd_end_date' => '2026-12-20',
        'even_start_date' => '2027-01-05', 'even_end_date' => '2027-06-20',
    ]);
    $tUser = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XII-A']);
    $other = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XII-B']);
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
        app(ScheduleSettingService::class)->updateDay($day, [['start' => '07:00', 'end' => '08:00']]);
    }
    app(ScheduleSettingService::class)->updateDay('saturday', []);
    app(ScheduleSettingService::class)->updateDay('sunday', []);
    ClassSchedule::create(['classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0, 'start_time' => '07:00', 'end_time' => '08:00', 'teacher_id' => $teacher->id]);

    $sUser = User::factory()->student()->create(['name' => 'Siswa A', 'identity_number' => 'NIS001']);
    $student = Student::factory()->create(['user_id' => $sUser->id]);
    $classroom->students()->sync([$student->id]);

    $oUser = User::factory()->student()->create();
    $oStudent = Student::factory()->create(['user_id' => $oUser->id]);
    $other->students()->sync([$oStudent->id]);

    return [$tUser->createToken('t')->plainTextToken, $teacher, $year, $classroom, $other, $student, $oStudent, $sUser];
}

test('guests and non-teachers cannot access history', function (): void {
    $this->getJson('/teacher/students-history')->assertUnauthorized();
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('t')->plainTextToken;
    $this->withToken($token)->getJson('/teacher/students-history')->assertForbidden();
});

test('dropdown returns only taught classrooms plus selected scoping', function (): void {
    [$token, $teacher, $year, $classroom, $other] = historySetup();

    $this->withToken($token)->getJson('/teacher/classrooms/dropdown')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $classroom->id)
        ->assertJsonPath('meta.total', 1);

    $this->withToken($token)->getJson("/teacher/classrooms/dropdown/selected?active_ids[]={$classroom->id}&active_ids[]={$other->id}")->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $classroom->id);
});

test('index filters by class and semester with attendance math', function (): void {
    [$token, $teacher, $year, $classroom, $other, $student, $oStudent, $sUser] = historySetup();

    $session = PresenceSession::create([
        'class_schedule_id' => ClassSchedule::first()->id, 'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id, 'date' => '2026-10-05', 'status' => 'closed',
        'started_at' => now(), 'closed_at' => now(), 'total_students' => 1,
    ]);
    $session->records()->create(['student_id' => $student->id, 'user_id' => $sUser->id, 'scanned_at' => now(), 'inside_zone' => true]);
    PublicHoliday::factory()->create(['date' => '2026-10-06']);
    LeaveRequest::factory()->create([
        'user_id' => $sUser->id, 'type' => LeaveRequestTypeEnum::SickLeave, 'status' => LeaveRequestStatusEnum::Approved,
        'start_date' => '2026-10-07', 'end_date' => '2026-10-07', 'range_date' => ['2026-10-07'],
    ]);

    $this->withToken($token)->getJson('/teacher/students-history?semester=1')->assertOk()
        ->assertJsonPath('data.0.student.identity_number', 'NIS001')
        ->assertJsonPath('data.0.class.name', 'XII-A')
        ->assertJsonStructure(['data' => [['student' => ['id', 'name', 'email', 'identity_number'], 'attendance' => ['present', 'sick_leave', 'permit', 'alpha', 'attendance_percentage']]]]);

    $data = $this->withToken($token)->getJson('/teacher/students-history?semester=1')->json('data.0.attendance');
    expect($data['present'])->toBe(1)->and($data['sick_leave'])->toBe(1)
        ->and($data['present'] + $data['sick_leave'] + $data['permit'] + $data['alpha'])->toBe($data['total_effective_days']);

    $this->withToken($token)->getJson("/teacher/students-history?classroom_id={$other->id}")->assertNotFound();
    $this->withToken($token)->getJson("/teacher/students-history?classroom_id={$classroom->id}")->assertOk()->assertJsonPath('meta.total', 1);
});

test('detail returns calendar plus academic year and scopes students', function (): void {
    [$token, $teacher, $year, $classroom, $other, $student, $oStudent, $sUser] = historySetup();

    $session = PresenceSession::create([
        'class_schedule_id' => ClassSchedule::first()->id, 'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id, 'date' => '2026-10-05', 'status' => 'closed',
        'started_at' => now(), 'closed_at' => now(), 'total_students' => 1,
    ]);
    $session->records()->create(['student_id' => $student->id, 'user_id' => $sUser->id, 'scanned_at' => now(), 'inside_zone' => true]);
    LeaveRequest::factory()->create([
        'user_id' => $sUser->id, 'type' => LeaveRequestTypeEnum::SickLeave, 'status' => LeaveRequestStatusEnum::Approved,
        'start_date' => '2026-10-07', 'end_date' => '2026-10-07', 'range_date' => ['2026-10-07'],
    ]);

    $this->withToken($token)->getJson("/teacher/students-history/{$student->id}?month=10&year=2026")->assertOk()
        ->assertJsonPath('data.academic_year.id', $year->id)
        ->assertJsonPath('data.month', '2026-10')
        ->assertJsonPath('data.attendance.present', 1)
        ->assertJsonPath('data.attendance.sick_leave', 1)
        ->assertJsonStructure(['data' => ['student', 'attendance', 'calendar', 'academic_year']]);

    $this->withToken($token)->getJson("/teacher/students-history/{$oStudent->id}?month=10&year=2026")->assertNotFound();
    $this->withToken($token)->getJson('/teacher/students-history/999999?month=10&year=2026')->assertNotFound();
});
