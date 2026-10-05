<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homeroomTeacherToken(): array
{
    $user = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $user->id]);

    return [$user->createToken('auth-token')->plainTextToken, $teacher];
}

test('guests cannot access homeroom', function (): void {
    $this->getJson('/homeroom')->assertUnauthorized();
    $this->getJson('/homeroom/students')->assertUnauthorized();
    $this->getJson('/homeroom/students/1')->assertUnauthorized();
});

test('non-teachers are forbidden from homeroom', function (): void {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('auth-token')->plainTextToken;
    $student = Student::factory()->create();

    $this->withToken($token)->getJson('/homeroom')->assertForbidden();
    $this->withToken($token)->getJson('/homeroom/students')->assertForbidden();
    $this->withToken($token)->getJson('/homeroom/students/'.$student->id)->assertForbidden();
});

test('teacher without homeroom gets empty payload and students 404', function (): void {
    [$token] = homeroomTeacherToken();
    AcademicYear::factory()->active()->create();

    $this->withToken($token)->getJson('/homeroom')->assertOk()
        ->assertJsonPath('data.has_homeroom', false)
        ->assertJsonPath('data.class', null);

    $this->withToken($token)->getJson('/homeroom/students')->assertNotFound();
});

test('teacher homeroom resolves active year only and lists students like index', function (): void {
    [$token, $teacher] = homeroomTeacherToken();
    $activeYear = AcademicYear::factory()->active()->create();
    $oldYear = AcademicYear::factory()->create(['is_active' => false]);
    Classroom::factory()->create(['academic_year_id' => $oldYear->id, 'homeroom_teacher_id' => $teacher->id, 'name' => 'Old Class']);
    $classroom = Classroom::factory()->create(['academic_year_id' => $activeYear->id, 'homeroom_teacher_id' => $teacher->id, 'name' => 'XII F 3']);
    $students = Student::factory()->count(3)->create();
    $classroom->students()->sync($students->pluck('id')->all());

    $this->withToken($token)->getJson('/homeroom')->assertOk()
        ->assertJsonPath('data.has_homeroom', true)
        ->assertJsonPath('data.class.id', $classroom->id)
        ->assertJsonPath('data.class.name', 'XII F 3');

    $this->withToken($token)->getJson('/homeroom/students')->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure(['data' => [['id', 'gender', 'status']]]);

    $this->withToken($token)->getJson('/homeroom/students?search='.$students[0]->user->name)->assertOk();
});

test('homeroom student detail returns scoped student or 404', function (): void {
    [$token, $teacher] = homeroomTeacherToken();
    $activeYear = AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $activeYear->id, 'homeroom_teacher_id' => $teacher->id]);
    $inClass = Student::factory()->create();
    $outClass = Student::factory()->create();
    $classroom->students()->sync([$inClass->id]);

    $this->withToken($token)->getJson('/homeroom/students/'.$inClass->id)->assertOk()
        ->assertJsonPath('data.id', $inClass->id)
        ->assertJsonStructure(['data' => ['id', 'gender', 'status']]);

    $this->withToken($token)->getJson('/homeroom/students/'.$outClass->id)->assertNotFound();
    $this->withToken($token)->getJson('/homeroom/students/999999')->assertNotFound();
});
