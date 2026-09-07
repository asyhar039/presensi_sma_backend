<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function subjectAdminToken(): string
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    return $user->createToken('auth-token')->plainTextToken;
}

test('guests cannot access teacher-subjects', function (): void {
    $this->getJson('/teacher-subjects')->assertUnauthorized();
    $this->postJson('/teacher-subjects', [])->assertUnauthorized();
});

test('admin can assign teacher into subject using active year by default', function (): void {
    $token = subjectAdminToken();
    $year = AcademicYear::factory()->active()->create();
    $teacher = Teacher::factory()->create();
    $subject = Subject::factory()->create();

    $created = $this->withToken($token)->postJson('/teacher-subjects', [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ])->assertCreated()->json('data');

    expect($created['academic_year']['id'])->toBe($year->id);

    $this->withToken($token)->postJson('/teacher-subjects', [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ])->assertUnprocessable();

    $this->withToken($token)->getJson('/teacher-subjects')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    $this->withToken($token)->deleteJson("/teacher-subjects/{$created['id']}")->assertOk();
    $this->assertDatabaseMissing('teacher_subjects', ['id' => $created['id']]);
});

test('teacher-subject assignment can target explicit academic year', function (): void {
    $token = subjectAdminToken();
    AcademicYear::factory()->active()->create();
    $other = AcademicYear::factory()->create();
    $teacher = Teacher::factory()->create();
    $subject = Subject::factory()->create();

    $created = $this->withToken($token)->postJson('/teacher-subjects', [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $other->id,
    ])->assertCreated()->json('data');

    expect($created['academic_year']['id'])->toBe($other->id);
});
