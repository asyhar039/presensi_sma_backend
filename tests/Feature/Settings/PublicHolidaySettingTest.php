<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publicHolidayToken(string $role = 'admin'): string
{
    $user = User::factory()->create(['role' => $role === 'admin' ? RoleEnum::Admin : RoleEnum::Student]);

    return $user->createToken('auth-token')->plainTextToken;
}

function validHolidays(): array
{
    return [
        ['name' => 'New Year', 'date' => '2026-01-01'],
        ['name' => 'Independence Day', 'date' => '2026-08-17'],
    ];
}

test('guests cannot access public holidays', function (): void {
    $this->getJson('/settings/public-holidays')->assertUnauthorized();
    $this->putJson('/settings/public-holidays', [])->assertUnauthorized();
});

test('non-admin users are forbidden from public holidays', function (): void {
    $token = publicHolidayToken('student');

    $this->withToken($token)->getJson('/settings/public-holidays')->assertForbidden();
    $this->withToken($token)->putJson('/settings/public-holidays', [])->assertForbidden();
});

test('admin can replace and list public holidays without pagination', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->getJson('/settings/public-holidays')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonMissingPath('meta');

    $this->withToken($token)->putJson('/settings/public-holidays', validHolidays())
        ->assertOk()
        ->assertJsonPath('data.0.date', '2026-01-01')
        ->assertJsonPath('data.1.date', '2026-08-17');

    $this->withToken($token)->getJson('/settings/public-holidays')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('meta');
});

test('public holidays are stored ordered by date', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->putJson('/settings/public-holidays', array_reverse(validHolidays()))
        ->assertOk()
        ->assertJsonPath('data.0.date', '2026-01-01');
});

test('public holidays reject duplicate dates and names', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->putJson('/settings/public-holidays', [
        ['name' => 'New Year', 'date' => '2026-01-01'],
        ['name' => 'Second New Year', 'date' => '2026-01-01'],
    ])->assertUnprocessable();

    $this->withToken($token)->putJson('/settings/public-holidays', [
        ['name' => 'New Year', 'date' => '2026-01-01'],
        ['name' => 'New Year', 'date' => '2026-12-25'],
    ])->assertUnprocessable();
});

test('public holidays reject invalid dates', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->putJson('/settings/public-holidays', [
        ['name' => 'Impossible Day', 'date' => '2026-02-30'],
    ])->assertUnprocessable();

    $this->withToken($token)->putJson('/settings/public-holidays', [
        ['name' => 'Wrong Format', 'date' => '30-02-2026'],
    ])->assertUnprocessable();
});

test('admin can delete a public holiday by date', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->putJson('/settings/public-holidays', validHolidays())->assertOk();

    $this->withToken($token)->deleteJson('/settings/public-holidays/2026-01-01')->assertOk();

    $this->withToken($token)->getJson('/settings/public-holidays')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', '2026-08-17');

    $this->withToken($token)->deleteJson('/settings/public-holidays/2026-01-01')->assertNotFound();
});
