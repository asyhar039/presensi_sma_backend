<?php

use App\Enums\DayEnum;
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

test('teacher schedule requires teacher role', function (): void {
    $this->getJson('/subject/schedules')->assertUnauthorized();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/subject/schedules')->assertForbidden();
});

test('teacher schedules are grouped by day with empty days included', function (): void {
    $year = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XI F 1']);
    $otherClass = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'XI F 2']);

    [$teacherUser, $teacher] = makeScheduleTeacher();
    [, $other] = makeScheduleTeacher();

    $history = Subject::factory()->create(['name' => 'Sejarah']);
    $advanced = Subject::factory()->create(['name' => 'Sejarah Tingkat Lanjut']);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => $history->id, 'academic_year_id' => $year->id]);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => $advanced->id, 'academic_year_id' => $year->id]);
    TeacherSubject::create(['teacher_id' => $teacher->id, 'subject_id' => Subject::factory()->create()->id, 'academic_year_id' => AcademicYear::factory()->create()->id]);

    ClassSchedule::create(['classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $teacher->id]);
    ClassSchedule::create(['classroom_id' => $otherClass->id, 'day' => 'monday', 'period' => 1, 'start_time' => '07:00', 'end_time' => '08:00', 'teacher_id' => $teacher->id]);
    ClassSchedule::create(['classroom_id' => $otherClass->id, 'day' => 'tuesday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $teacher->id]);
    ClassSchedule::create(['classroom_id' => $otherClass->id, 'day' => 'monday', 'period' => 0, 'start_time' => '08:00', 'end_time' => '10:30', 'teacher_id' => $other->id]);

    $data = $this->actingAs($teacherUser)->getJson('/subject/schedules')->assertOk()->json('data');

    expect(array_keys($data))->toBe(DayEnum::values())
        ->and($data['monday'])->toHaveCount(2)
        ->and($data['tuesday'])->toHaveCount(1)
        ->and($data['wednesday'])->toBe([]);

    expect($data['monday'][0]['start_time'])->toBe('07:00')
        ->and($data['monday'][1]['classroom'])->toBe(['id' => $classroom->id, 'name' => 'XI F 1'])
        ->and($data['monday'][1]['day'])->toBe('monday')
        ->and($data['monday'][1]['subjects'])->toBe([
            ['id' => $history->id, 'name' => 'Sejarah'],
            ['id' => $advanced->id, 'name' => 'Sejarah Tingkat Lanjut'],
        ]);
});
