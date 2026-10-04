<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function classroomAdminToken(): string
{
    $user = User::factory()->admin()->create();

    return $user->createToken('auth-token')->plainTextToken;
}

test('guests cannot access classrooms', function (): void {
    $this->getJson('/classrooms')->assertUnauthorized();
    $this->postJson('/classrooms', [])->assertUnauthorized();
});

test('classroom creation uses active academic year by default', function (): void {
    $token = classroomAdminToken();
    $year = AcademicYear::factory()->active()->create();

    $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertCreated()
        ->assertJsonPath('message', 'Classroom created successfully.')
        ->assertJsonPath('data', null);

    $created = $this->withToken($token)->getJson('/classrooms')->assertOk()->json('data.0');

    expect($created['name'])->toBe('XII IPA 1')
        ->and($created['academic_year']['value'])->toBe($year->id);
});

test('classroom creation fails without academic year context', function (): void {
    $token = classroomAdminToken();

    $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertUnprocessable();
});

test('admin can crud classrooms', function (): void {
    $token = classroomAdminToken();
    $year = AcademicYear::factory()->active()->create();

    $this->withToken($token)->postJson('/classrooms', ['name' => 'XII IPA 1'])
        ->assertCreated()->assertJsonPath('data', null);

    $created = $this->withToken($token)->getJson('/classrooms')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']])
        ->assertJsonStructure(['data' => [['id', 'name', 'academic_year', 'students_count']]])
        ->json('data.0');

    $this->withToken($token)->getJson("/classrooms/{$created['id']}")
        ->assertOk()->assertJsonPath('data.name', 'XII IPA 1');

    $this->withToken($token)->putJson("/classrooms/{$created['id']}", ['name' => 'XII IPA 2'])
        ->assertOk()->assertJsonPath('message', 'Classroom updated successfully.')
        ->assertJsonPath('data', null);

    $this->withToken($token)->getJson("/classrooms/{$created['id']}")
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
        ->assertOk()
        ->assertJsonPath('message', 'Homeroom teacher assigned successfully.')
        ->assertJsonPath('data', null);

    $this->withToken($token)->getJson("/classrooms/{$classroom->id}")
        ->assertOk()->assertJsonPath('data.homeroom_teacher.id', $teacher->id);

    $other = Classroom::factory()->create(['academic_year_id' => $classroom->academic_year_id, 'name' => 'Other Class']);
    $this->withToken($token)->putJson("/classrooms/{$other->id}/homeroom", ['homeroom_teacher_id' => $teacher->id])
        ->assertUnprocessable();

    $this->withToken($token)->deleteJson("/classrooms/{$classroom->id}/homeroom")
        ->assertOk()
        ->assertJsonPath('message', 'Homeroom teacher removed successfully.')
        ->assertJsonPath('data', null);
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

test('dropdowns return value-label options with hide filters', function (): void {
    $token = classroomAdminToken();
    $year = AcademicYear::factory()->active()->create();
    $teacher = Teacher::factory()->create();
    $students = Student::factory()->count(2)->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'homeroom_teacher_id' => $teacher->id]);
    $classroom->students()->attach($students->first()->id);

    $this->withToken($token)->getJson('/teachers/dropdown?hide_has_homeroom=1')
        ->assertOk()->assertJsonMissing(['value' => $teacher->id]);

    $this->withToken($token)->getJson('/students/dropdown?hide_has_classroom=1')
        ->assertOk()->assertJsonMissing(['value' => $students->first()->id])
        ->assertJsonFragment(['value' => $students->last()->id]);

    $this->withToken($token)->getJson('/academic-years/dropdown')
        ->assertOk()->assertJsonFragment(['value' => $year->id]);
});
