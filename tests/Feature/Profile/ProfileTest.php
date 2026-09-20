<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function adminToken(): array
{
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::Admin->value);

    return [$admin, $admin->createToken('auth-token')->plainTextToken];
}

test('admin can update own profile', function (): void {
    [$admin, $token] = adminToken();

    $response = $this->withToken($token)->putJson('/auth/profile', [
        'name' => 'New Admin Name',
        'email' => 'new-admin@example.com',
        'identity_number' => 'ADM999',
        'phone_number' => '081299999999',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Admin Name')
        ->assertJsonPath('data.email', 'new-admin@example.com')
        ->assertJsonPath('data.identity_number', 'ADM999')
        ->assertJsonPath('data.phone_number', '081299999999');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'name' => 'New Admin Name',
        'email' => 'new-admin@example.com',
    ]);
});

test('profile update keeps own email without unique violation', function (): void {
    [$admin, $token] = adminToken();

    $this->withToken($token)->putJson('/auth/profile', [
        'name' => 'Same Email',
        'email' => $admin->email,
        'identity_number' => $admin->identity_number,
        'phone_number' => $admin->phone_number,
    ])->assertOk()->assertJsonPath('data.email', $admin->email);
});

test('profile update fails when email is taken by another user', function (): void {
    [$admin, $token] = adminToken();
    $other = User::factory()->create();

    $this->withToken($token)->putJson('/auth/profile', [
        'name' => 'New Admin Name',
        'email' => $other->email,
        'identity_number' => 'ADM999',
        'phone_number' => '081299999999',
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['email']]);
});

test('guests cannot access profile endpoints', function (): void {
    $this->getJson('/auth/profile')->assertUnauthorized();
    $this->putJson('/auth/profile', [])->assertUnauthorized();
    $this->putJson('/auth/profile/password', [])->assertUnauthorized();
});

test('admin can change password with correct current password', function (): void {
    [$admin, $token] = adminToken();

    $this->withToken($token)->putJson('/auth/profile/password', [
        'current_password' => 'password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertOk()->assertJsonPath('message', 'Password changed successfully.');

    expect(Hash::check('new-secret-password', $admin->refresh()->password))->toBeTrue();

    $this->postJson('/auth/login', [
        'email' => $admin->email,
        'password' => 'new-secret-password',
    ])->assertOk();
});

test('password change fails with wrong current password', function (): void {
    [, $token] = adminToken();

    $this->withToken($token)->putJson('/auth/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['current_password']]);
});

test('password change requires confirmation', function (): void {
    [, $token] = adminToken();

    $this->withToken($token)->putJson('/auth/profile/password', [
        'current_password' => 'password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'different-password',
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['password']]);
});
