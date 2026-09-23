<?php

use App\Enums\RoleEnum;
use App\Models\SchoolZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schoolZoneToken(string $role = 'admin'): string
{
    $user = User::factory()->create(['role' => $role === 'admin' ? RoleEnum::Admin : RoleEnum::Student]);

    return $user->createToken('auth-token')->plainTextToken;
}

function validZonePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'North Wing',
        'points' => [[-6.2, 106.8], [-6.21, 106.81], [-6.22, 106.79]],
    ], $overrides);
}

test('guests cannot access school zones', function (): void {
    $zone = SchoolZone::factory()->create();

    $this->getJson('/settings/school-zones')->assertUnauthorized();
    $this->postJson('/settings/school-zones', [])->assertUnauthorized();
    $this->getJson("/settings/school-zones/{$zone->id}")->assertUnauthorized();
    $this->putJson("/settings/school-zones/{$zone->id}", [])->assertUnauthorized();
    $this->patchJson("/settings/school-zones/{$zone->id}/active", [])->assertUnauthorized();
});

test('non-admin users are forbidden from school zones', function (): void {
    $token = schoolZoneToken('student');
    $zone = SchoolZone::factory()->create();

    $this->withToken($token)->getJson('/settings/school-zones')->assertForbidden();
    $this->withToken($token)->postJson('/settings/school-zones', [])->assertForbidden();
    $this->withToken($token)->getJson("/settings/school-zones/{$zone->id}")->assertForbidden();
    $this->withToken($token)->putJson("/settings/school-zones/{$zone->id}", [])->assertForbidden();
    $this->withToken($token)->patchJson("/settings/school-zones/{$zone->id}/active", [])->assertForbidden();
});

test('admin lists all school zones without pagination', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->getJson('/settings/school-zones')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonMissingPath('meta');

    SchoolZone::factory()->create(['name' => 'North Wing']);
    SchoolZone::factory()->create(['name' => 'South Wing', 'is_active' => false]);

    $this->withToken($token)->getJson('/settings/school-zones')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('meta')
        ->assertJsonPath('data.0.name', 'North Wing');
});

test('admin can filter school zones by active status', function (): void {
    $token = schoolZoneToken();
    SchoolZone::factory()->create(['name' => 'Active Zone', 'is_active' => true]);
    SchoolZone::factory()->create(['name' => 'Inactive Zone', 'is_active' => false]);

    $this->withToken($token)->getJson('/settings/school-zones?is_active=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Zone');

    $this->withToken($token)->getJson('/settings/school-zones?is_active=0')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inactive Zone');
});

test('admin can create a school zone defaulting to active', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'North Wing')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.points.0.0', -6.2);

    $this->assertDatabaseHas('school_zones', ['name' => 'North Wing', 'is_active' => true]);
});

test('admin can create an inactive school zone', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload(['name' => 'South Wing', 'is_active' => false]))
        ->assertCreated()
        ->assertJsonPath('data.is_active', false);
});

test('school zone names must be unique and at most 32 characters', function (): void {
    $token = schoolZoneToken();
    SchoolZone::factory()->create(['name' => 'North Wing']);

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload())
        ->assertUnprocessable();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload([
        'name' => str_repeat('a', 33),
    ]))->assertUnprocessable();
});

test('school zones reject invalid polygons', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload([
        'points' => [[-6.2, 106.8], [-6.21, 106.81]],
    ]))->assertUnprocessable();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload([
        'points' => [[-91.0, 106.8], [-6.21, 106.81], [-6.22, 106.79]],
    ]))->assertUnprocessable();

    $this->withToken($token)->postJson('/settings/school-zones', validZonePayload([
        'points' => [[-6.2, 106.8], [-6.2, 106.8], [-6.22, 106.79]],
    ]))->assertUnprocessable();
});

test('admin can show and update a school zone individually', function (): void {
    $token = schoolZoneToken();
    $zone = SchoolZone::factory()->create(['name' => 'North Wing']);

    $this->withToken($token)->getJson("/settings/school-zones/{$zone->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $zone->id);

    $this->withToken($token)->putJson("/settings/school-zones/{$zone->id}", [
        'name' => 'Renamed Wing',
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed Wing')
        ->assertJsonPath('data.is_active', false);

    $this->withToken($token)->putJson("/settings/school-zones/{$zone->id}", [
        'name' => 'Renamed Wing',
    ])->assertOk();

    $other = SchoolZone::factory()->create(['name' => 'Other Wing']);

    $this->withToken($token)->putJson("/settings/school-zones/{$zone->id}", [
        'name' => 'Other Wing',
    ])->assertUnprocessable();

    $this->withToken($token)->putJson('/settings/school-zones/999999', ['name' => 'Missing'])
        ->assertNotFound();
});

test('admin can update a school zone active status', function (): void {
    $token = schoolZoneToken();
    $zone = SchoolZone::factory()->create(['is_active' => true]);

    $this->withToken($token)->patchJson("/settings/school-zones/{$zone->id}/active", [
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $zone->id)
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('school_zones', ['id' => $zone->id, 'is_active' => false]);

    $this->withToken($token)->patchJson("/settings/school-zones/{$zone->id}/active", [
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    $this->withToken($token)->patchJson("/settings/school-zones/{$zone->id}/active", [])
        ->assertUnprocessable();

    $this->withToken($token)->patchJson('/settings/school-zones/999999/active', [
        'is_active' => true,
    ])->assertNotFound();
});
