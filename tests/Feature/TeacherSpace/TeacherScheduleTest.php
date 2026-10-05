<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeScheduleTeacher(): array
{
    $user = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);

    return [$user, $teacher];
}

test('teacher schedule requires teacher role and valid day', function (): void {
    $this->getJson('/subject/schedules?day=monday')->assertUnauthorized();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->getJson('/subject/schedules?day=monday')->assertForbidden();

    [$teacherUser] = makeScheduleTeacher();
    $this->actingAs($teacherUser)->getJson('/subject/schedules')->assertUnprocessable();
    $this->actingAs($teacherUser)->getJson('/subject/schedules?day=funday')->assertUnprocessable();
});

test('teacher sees only own schedules on the given day', function (): void {
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XI F 1']);
    $otherClass = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XI F 2']);

    [$teacherUser, $teacher] = makeScheduleTeacher();
    [, $other] = makeScheduleTeacher();

    $history = Subject::factory()->create(['name' => 'Sejarah']);
    $advanced = Subject::factory()->create(['name' => 'Sejarah Tingkat Lanjut']);
    $otherYear = AcademicYear::factory()->create();
    $stale = Subject::factory()->create(['name' => 'Stale']);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => $history->id, 'academic_year_id' => $year->id]);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => $advanced->id, 'academic_year_id' => $year->id]);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => $stale->id, 'academic_year_id' => $otherYear->id]);

    ClassSchedule::create(['classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $teacher->id]);
    ClassSchedule::create(['classroom_id' => $otherClass->id, 'day' => 'tuesday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $teacher->id]);
    ClassSchedule::create(['classroom_id' => $otherClass->id, 'day' => 'monday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $other->id]);

    $data = $this->actingAs($teacherUser)->getJson('/subject/schedules?day=Monday')
        ->assertOk()->assertJsonCount(1, 'data')->json('data');

    expect($data[0]['classroom'])->toBe(['id' => $classroom->id, 'name' => 'XI F 1'])
        ->and($data[0]['day'])->toBe('monday')
        ->and($data[0]['start_time'])->toBe('08:00')
        ->and($data[0]['end_time'])->toBe('10:30')
        ->and($data[0]['subjects'])->toBe([
            ['id' => $history->id, 'name' => 'Sejarah'],
            ['id' => $advanced->id, 'name' => 'Sejarah Tingkat Lanjut'],
        ]);

    $this->actingAs($teacherUser)->getJson('/subject/schedules?day=tuesday')->assertOk()->assertJsonCount(1, 'data');
});
