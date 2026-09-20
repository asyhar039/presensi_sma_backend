<?php

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function schoolAdminToken(string $role = 'admin'): string
{
    $user = User::factory()->create();
    $user->assignRole($role === 'admin' ? RoleEnum::Admin->value : RoleEnum::Student->value);

    return $user->createToken('auth-token')->plainTextToken;
}

function studentPayload(array $overrides = []): array
{
    return array_merge([
        'identity_number' => 'STD'.fake()->unique()->numerify('######'),
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'phone_number' => fake()->unique()->phoneNumber(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'gender' => 'male',
        'address' => 'Jl. Pelajar No. 1',
        'status' => 'active',
    ], $overrides);
}

function teacherPayload(array $overrides = []): array
{
    return array_merge([
        'identity_number' => 'TCH'.fake()->unique()->numerify('######'),
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'phone_number' => fake()->unique()->phoneNumber(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'gender' => 'female',
        'address' => 'Jl. Pendidikan No. 1',
        'employment_status' => 'pns',
    ], $overrides);
}

test('guests cannot access students or teachers', function (): void {
    $this->getJson('/students')->assertUnauthorized();
    $this->postJson('/students', [])->assertUnauthorized();
    $this->getJson('/teachers')->assertUnauthorized();
    $this->postJson('/teachers', [])->assertUnauthorized();
});

test('non-admin users are forbidden from students and teachers', function (): void {
    $token = schoolAdminToken('student');

    $this->withToken($token)->getJson('/students')->assertForbidden();
    $this->withToken($token)->postJson('/students', studentPayload())->assertForbidden();
    $this->withToken($token)->getJson('/teachers')->assertForbidden();
    $this->withToken($token)->postJson('/teachers', teacherPayload())->assertForbidden();
});

test('admin can crud students', function (): void {
    $token = schoolAdminToken();

    $created = $this->withToken($token)->postJson('/students', studentPayload())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'user', 'gender', 'address', 'status']])
        ->json('data');

    $this->assertDatabaseHas('users', ['id' => $created['user']['id'], 'email' => $created['user']['email']]);
    $student = Student::find($created['id']);
    expect($student->user->hasRole(RoleEnum::Student->value))->toBeTrue();

    $this->withToken($token)->getJson('/students')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']]);

    $this->withToken($token)->getJson("/students/{$created['id']}")
        ->assertOk()->assertJsonPath('data.id', $created['id']);

    $this->withToken($token)->putJson("/students/{$created['id']}", ['name' => 'Updated Name', 'status' => 'inactive'])
        ->assertOk()->assertJsonPath('data.user.name', 'Updated Name');

    $this->withToken($token)->deleteJson("/students/{$created['id']}")->assertOk();
    $this->assertDatabaseMissing('students', ['id' => $created['id']]);
    $this->assertDatabaseMissing('users', ['id' => $created['user']['id']]);
});

test('admin can change student password', function (): void {
    $token = schoolAdminToken();

    $created = $this->withToken($token)->postJson('/students', studentPayload())->assertCreated()->json('data');

    $this->withToken($token)->putJson("/students/{$created['id']}/password", [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertOk()->assertJsonPath('message', 'Student password changed successfully.');

    expect(Hash::check('newpassword123', User::find($created['user']['id'])->password))->toBeTrue();
});

test('admin can crud teachers', function (): void {
    $token = schoolAdminToken();

    $created = $this->withToken($token)->postJson('/teachers', teacherPayload())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'user', 'gender', 'address', 'employment_status']])
        ->json('data');

    $teacher = Teacher::find($created['id']);
    expect($teacher->user->hasRole(RoleEnum::Teacher->value))->toBeTrue();

    $this->withToken($token)->getJson('/teachers')->assertOk();

    $this->withToken($token)->getJson("/teachers/{$created['id']}")
        ->assertOk()->assertJsonPath('data.id', $created['id']);

    $this->withToken($token)->putJson("/teachers/{$created['id']}", ['name' => 'Updated Teacher', 'employment_status' => 'honorer'])
        ->assertOk()->assertJsonPath('data.user.name', 'Updated Teacher');

    $this->withToken($token)->deleteJson("/teachers/{$created['id']}")->assertOk();
    $this->assertDatabaseMissing('teachers', ['id' => $created['id']]);
    $this->assertDatabaseMissing('users', ['id' => $created['user']['id']]);
});

test('admin can change teacher password', function (): void {
    $token = schoolAdminToken();

    $created = $this->withToken($token)->postJson('/teachers', teacherPayload())->assertCreated()->json('data');

    $this->withToken($token)->putJson("/teachers/{$created['id']}/password", [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertOk();

    expect(Hash::check('newpassword123', User::find($created['user']['id'])->password))->toBeTrue();
});

test('student creation validates unique email', function (): void {
    $token = schoolAdminToken();
    $payload = studentPayload();

    $this->withToken($token)->postJson('/students', $payload)->assertCreated();
    $this->withToken($token)->postJson('/students', studentPayload(['email' => $payload['email']]))
        ->assertUnprocessable();
});
