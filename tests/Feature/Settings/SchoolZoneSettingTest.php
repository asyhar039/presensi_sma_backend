<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schoolZoneToken(string $role = 'admin'): string
{
    $user = User::factory()->create(['role' => $role === 'admin' ? RoleEnum::Admin : RoleEnum::Student]);

    return $user->createToken('auth-token')->plainTextToken;
}

function validZones(): array
{
    return [
        [
            'name' => 'North Wing',
            'points' => [[-6.2, 106.8], [-6.21, 106.81], [-6.22, 106.79]],
        ],
        [
            'name' => 'South Wing',
            'points' => [[-6.3, 106.9], [-6.31, 106.91], [-6.32, 106.89], [-6.33, 106.88]],
        ],
    ];
}

test('guests cannot access school zones', function (): void {
    $this->getJson('/settings/school-zones')->assertUnauthorized();
    $this->putJson('/settings/school-zones', [])->assertUnauthorized();
});

test('non-admin users are forbidden from school zones', function (): void {
    $token = schoolZoneToken('student');

    $this->withToken($token)->getJson('/settings/school-zones')->assertForbidden();
    $this->withToken($token)->putJson('/settings/school-zones', [])->assertForbidden();
});

test('admin can replace and list school zones without pagination', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->getJson('/settings/school-zones')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonMissingPath('meta');

    $this->withToken($token)->putJson('/settings/school-zones', validZones())
        ->assertOk()
        ->assertJsonPath('data.0.name', 'North Wing')
        ->assertJsonPath('data.0.points.0.0', -6.2);

    $this->withToken($token)->getJson('/settings/school-zones')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('meta');
});

test('school zones reject polygons with fewer than three points', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->putJson('/settings/school-zones', [
        ['name' => 'Tiny', 'points' => [[-6.2, 106.8], [-6.21, 106.81]]],
    ])->assertUnprocessable();
});

test('school zones reject out-of-range coordinates and duplicate names', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->putJson('/settings/school-zones', [
        ['name' => 'Far Away', 'points' => [[-91.0, 106.8], [-6.21, 106.81], [-6.22, 106.79]]],
    ])->assertUnprocessable();

    $this->withToken($token)->putJson('/settings/school-zones', [
        ['name' => 'North Wing', 'points' => [[-6.2, 106.8], [-6.21, 106.81], [-6.22, 106.79]]],
        ['name' => 'North Wing', 'points' => [[-6.3, 106.9], [-6.31, 106.91], [-6.32, 106.89]]],
    ])->assertUnprocessable();
});

test('admin can delete a school zone by name', function (): void {
    $token = schoolZoneToken();

    $this->withToken($token)->putJson('/settings/school-zones', validZones())->assertOk();

    $this->withToken($token)->deleteJson('/settings/school-zones/North%20Wing')->assertOk();

    $this->withToken($token)->getJson('/settings/school-zones')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'South Wing');

    $this->withToken($token)->deleteJson('/settings/school-zones/North%20Wing')->assertNotFound();
});
