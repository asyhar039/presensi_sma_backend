<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function classroomAdminToken(): string
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    return $user->createToken('auth-token')->plainTextToken;
}

test('guests cannot access classrooms', function (): void {
    $this->getJson('/classrooms')->assertUnauthorized();
    $this->postJson('/classrooms', [])->assertUnauthorized();
});

test('classroom creation uses active academic year by default', function (): void {
    $token = classroomAdminToken();
    $year = AcademicYear::factory()->active()->create();

    $created = $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'XII IPA 1')
        ->json('data');

    expect($created['academic_year']['id'])->toBe($year->id);
});

test('classroom creation fails without academic year context', function (): void {
    $token = classroomAdminToken();

    $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertUnprocessable();
});

test('admin can crud classrooms', function (): void {
    $token = classroomAdminToken();
    $year = AcademicYear::factory()->active()->create();

    $created = $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertCreated()->json('data');

    $this->withToken($token)->getJson('/classrooms')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    $this->withToken($token)->getJson("/classrooms/{$created['id']}")
        ->assertOk()->assertJsonPath('data.name', 'XII IPA 1');

    $this->withToken($token)->putJson("/classrooms/{$created['id']}", ['name' => 'XII IPA 2'])
        ->assertOk()->assertJsonPath('data.name', 'XII IPA 2');

    $this->withToken($token)->deleteJson("/classrooms/{$created['id']}")->assertOk();
    $this->assertDatabaseMissing('classrooms', ['id' => $created['id']]);
});

test('admin can assign and remove homeroom teacher', function (): void {
    $token = classroomAdminToken();
    AcademicYear::factory()->active()->create();
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => AcademicYear::query()->active()->value('id')]);

    $this->withToken($token)->putJson("/classrooms/{$classroom->id}/homeroom", ['homeroom_teacher_id' => $teacher->id])
        ->assertOk()->assertJsonPath('data.homeroom_teacher.id', $teacher->id);

    $other = Classroom::factory()->create(['academic_year_id' => $classroom->academic_year_id, 'name' => 'Other Class']);
    $this->withToken($token)->putJson("/classrooms/{$other->id}/homeroom", ['homeroom_teacher_id' => $teacher->id])
        ->assertUnprocessable();

    $this->withToken($token)->deleteJson("/classrooms/{$classroom->id}/homeroom")
        ->assertOk()->assertJsonPath('data.homeroom_teacher', null);
});

test('admin can assign students into classroom', function (): void {
    $token = classroomAdminToken();
    AcademicYear::factory()->active()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => AcademicYear::query()->active()->value('id')]);
    $students = Student::factory()->count(2)->create();

    $this->withToken($token)->postJson("/classrooms/{$classroom->id}/students", [
        'student_ids' => $students->pluck('id')->all(),
    ])->assertOk()->assertJsonPath('message', 'Students assigned to classroom successfully.');

    $this->withToken($token)->getJson("/classrooms/{$classroom->id}/students")
        ->assertOk()->assertJsonCount(2, 'data');

    $this->withToken($token)->deleteJson("/classrooms/{$classroom->id}/students/{$students->first()->id}")
        ->assertOk();

    $this->withToken($token)->getJson("/classrooms/{$classroom->id}/students")
        ->assertOk()->assertJsonCount(1, 'data');
});
