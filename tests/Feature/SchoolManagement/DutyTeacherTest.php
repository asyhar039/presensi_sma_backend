<?php

use App\Models\AcademicYear;
use App\Models\DutyTeacher;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dutyTeacherToken(string $role = 'admin'): string
{
    $factory = $role === 'admin' ? User::factory()->admin() : User::factory()->student();

    return $factory->create()->createToken('auth-token')->plainTextToken;
}

function dutyPayload(int $teacherId, ?int $yearId = null): array
{
    return array_filter([
        'teacher_id' => $teacherId,
        'academic_year_id' => $yearId,
        'day' => 'monday',
        'start_time' => '07:00',
        'end_time' => '08:00',
    ]);
}

test('guests and non-admins cannot access duty teachers', function (): void {
    $this->getJson('/duty-teachers')->assertUnauthorized();
    $this->postJson('/duty-teachers', [])->assertUnauthorized();

    $token = dutyTeacherToken('student');
    $this->withToken($token)->getJson('/duty-teachers')->assertForbidden();
    $this->withToken($token)->postJson('/duty-teachers', [])->assertForbidden();
});

test('admin can crud duty teachers with active year default and overlap guard', function (): void {
    $token = dutyTeacherToken();
    $year = AcademicYear::factory()->active()->create();
    $teacher = Teacher::factory()->create();

    $this->withToken($token)->postJson('/duty-teachers', dutyPayload($teacher->id))
        ->assertCreated()->assertJsonPath('data', null);

    $dutyTeacher = DutyTeacher::query()->firstOrFail();
    expect($dutyTeacher->academic_year_id)->toBe($year->id);

    $this->withToken($token)->postJson('/duty-teachers', dutyPayload($teacher->id))
        ->assertUnprocessable()->assertJsonStructure(['data' => ['teacher_id']]);

    $this->withToken($token)->getJson('/duty-teachers')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']])
        ->assertJsonPath('data.0.teacher.id', $teacher->id)
        ->assertJsonPath('data.0.teacher.name', $teacher->user->name)
        ->assertJsonPath('data.0.teacher.email', $teacher->user->email)
        ->assertJsonPath('data.0.academic_year.id', $year->id)
        ->assertJsonPath('data.0.day', 'monday');

    $this->withToken($token)->getJson("/duty-teachers/{$dutyTeacher->id}")
        ->assertOk()->assertJsonPath('data.day', 'monday')
        ->assertJsonPath('data.teacher.name', $teacher->user->name);

    $this->withToken($token)->putJson("/duty-teachers/{$dutyTeacher->id}", ['day' => 'tuesday'])
        ->assertOk()->assertJsonPath('data', null);

    expect(DutyTeacher::query()->firstOrFail()->day->value)->toBe('tuesday');

    $this->withToken($token)->deleteJson("/duty-teachers/{$dutyTeacher->id}")->assertOk();
    $this->assertDatabaseMissing('duty_teachers', ['id' => $dutyTeacher->id]);
});

test('duty teacher list filters by academic year and searches by user name', function (): void {
    $token = dutyTeacherToken();
    $active = AcademicYear::factory()->active()->create();
    $other = AcademicYear::factory()->create();
    $teacher = Teacher::factory()->create(['user_id' => User::factory()->teacher()->create(['name' => 'Zara Unique'])->id]);
    $otherTeacher = Teacher::factory()->create(['user_id' => User::factory()->teacher()->create(['name' => 'Budi Other'])->id]);

    $this->withToken($token)->postJson('/duty-teachers', dutyPayload($teacher->id, $active->id))->assertCreated();
    $this->withToken($token)->postJson('/duty-teachers', dutyPayload($teacher->id, $other->id))->assertCreated();
    $this->withToken($token)->postJson('/duty-teachers', [
        'teacher_id' => $otherTeacher->id,
        'academic_year_id' => $active->id,
        'day' => 'tuesday',
        'start_time' => '07:00',
        'end_time' => '08:00',
    ])->assertCreated();

    $this->withToken($token)->getJson('/duty-teachers')
        ->assertOk()->assertJsonCount(2, 'data');

    $this->withToken($token)->getJson("/duty-teachers?academic_year_id={$other->id}")
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.academic_year.id', $other->id);

    $this->withToken($token)->getJson('/duty-teachers?search=Zara%20Unique')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.teacher.name', 'Zara Unique');
});
