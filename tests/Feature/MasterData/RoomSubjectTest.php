<?php

use App\Enums\RoleEnum;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function masterDataToken(string $role = 'admin'): string
{
    $user = User::factory()->create();
    $user->assignRole($role === 'admin' ? RoleEnum::Admin->value : RoleEnum::Student->value);

    return $user->createToken('auth-token')->plainTextToken;
}

test('guests cannot access rooms or subjects', function (): void {
    $this->getJson('/rooms')->assertUnauthorized();
    $this->postJson('/rooms', ['name' => 'A101'])->assertUnauthorized();
    $this->getJson('/subjects')->assertUnauthorized();
    $this->postJson('/subjects', ['name' => 'Math'])->assertUnauthorized();
});

test('non-admin users are forbidden from rooms and subjects', function (): void {
    $token = masterDataToken('student');

    $this->withToken($token)->getJson('/rooms')->assertForbidden();
    $this->withToken($token)->postJson('/rooms', ['name' => 'A101'])->assertForbidden();
    $this->withToken($token)->getJson('/subjects')->assertForbidden();
    $this->withToken($token)->postJson('/subjects', ['name' => 'Math'])->assertForbidden();
});

test('admin can crud rooms', function (): void {
    $token = masterDataToken();

    $created = $this->withToken($token)->postJson('/rooms', ['name' => 'A101'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'A101')
        ->json('data');

    $this->withToken($token)->postJson('/rooms', ['name' => 'A101'])
        ->assertUnprocessable()->assertJsonStructure(['data' => ['name']]);

    $this->withToken($token)->getJson('/rooms')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']]);

    $this->withToken($token)->getJson("/rooms/{$created['id']}")
        ->assertOk()->assertJsonPath('data.name', 'A101');

    $this->withToken($token)->putJson("/rooms/{$created['id']}", ['name' => 'A102'])
        ->assertOk()->assertJsonPath('data.name', 'A102');

    $this->withToken($token)->deleteJson("/rooms/{$created['id']}")->assertOk();

    $this->assertDatabaseMissing('rooms', ['id' => $created['id']]);
});

test('room update keeps own name without unique violation', function (): void {
    $token = masterDataToken();
    $room = Room::factory()->create(['name' => 'A101']);

    $this->withToken($token)->putJson("/rooms/{$room->id}", ['name' => 'A101'])->assertOk();
});

test('admin can crud subjects', function (): void {
    $token = masterDataToken();

    $created = $this->withToken($token)->postJson('/subjects', ['name' => 'Mathematics'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mathematics')
        ->json('data');

    $this->withToken($token)->postJson('/subjects', ['name' => 'Mathematics'])
        ->assertUnprocessable()->assertJsonStructure(['data' => ['name']]);

    $this->withToken($token)->getJson('/subjects')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']]);

    $this->withToken($token)->getJson("/subjects/{$created['id']}")
        ->assertOk()->assertJsonPath('data.name', 'Mathematics');

    $this->withToken($token)->putJson("/subjects/{$created['id']}", ['name' => 'Physics'])
        ->assertOk()->assertJsonPath('data.name', 'Physics');

    $this->withToken($token)->deleteJson("/subjects/{$created['id']}")->assertOk();

    $this->assertDatabaseMissing('subjects', ['id' => $created['id']]);
});

test('subject update keeps own name without unique violation', function (): void {
    $token = masterDataToken();
    $subject = Subject::factory()->create(['name' => 'Mathematics']);

    $this->withToken($token)->putJson("/subjects/{$subject->id}", ['name' => 'Mathematics'])->assertOk();
});
