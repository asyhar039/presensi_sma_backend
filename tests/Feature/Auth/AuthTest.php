<?php

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login and receive a token', function (): void {
    $user = User::factory()->admin()->create();

    $response = $this->postJson('/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'data' => ['token', 'token_type', 'user' => ['id', 'email', 'role']]])
        ->assertJsonPath('data.token_type', 'Bearer');
});

test('login fails with invalid credentials', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Validation failed.')
        ->assertJsonStructure(['data' => ['email']]);
});

test('login requires email and password', function (): void {
    $this->postJson('/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['email', 'password']]);
});

test('authenticated user can fetch me with role', function (): void {
    $user = User::factory()->admin()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure(['data' => ['role']]);
});

test('guests cannot access me, information, or logout', function (): void {
    $this->getJson('/auth/me')->assertUnauthorized();
    $this->getJson('/auth/information')->assertUnauthorized();
    $this->postJson('/auth/logout')->assertUnauthorized();
});

test('user can logout and token is revoked', function (): void {
    $user = User::factory()->teacher()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)->postJson('/auth/logout')->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
    ]);
});

test('information returns teacher profile for teacher', function (): void {
    $teacherUser = User::factory()->teacher()->create();
    Teacher::factory()->for($teacherUser)->create();
    $token = $teacherUser->createToken('auth-token')->plainTextToken;

    $this->withToken($token)->getJson('/auth/information')
        ->assertOk()
        ->assertJsonPath('data.role', RoleEnum::Teacher->value)
        ->assertJsonPath('data.profile_type', 'teacher')
        ->assertJsonStructure(['data' => ['user', 'profile']]);
});

test('information returns student profile for student', function (): void {
    $studentUser = User::factory()->student()->create();
    Student::factory()->for($studentUser)->create();
    $token = $studentUser->createToken('auth-token')->plainTextToken;

    $this->withToken($token)->getJson('/auth/information')
        ->assertOk()
        ->assertJsonPath('data.role', RoleEnum::Student->value)
        ->assertJsonPath('data.profile_type', 'student')
        ->assertJsonStructure(['data' => ['user', 'profile']]);
});

test('information returns null profile for admin without detail', function (): void {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('auth-token')->plainTextToken;

    $this->withToken($token)->getJson('/auth/information')
        ->assertOk()
        ->assertJsonPath('data.role', RoleEnum::Admin->value)
        ->assertJsonPath('data.profile', null)
        ->assertJsonPath('data.profile_type', null);
});
