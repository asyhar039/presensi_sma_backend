<?php

use App\Enums\RoleEnum;
use App\Models\PublicHoliday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publicHolidayToken(string $role = 'admin'): string
{
    $user = User::factory()->create(['role' => $role === 'admin' ? RoleEnum::Admin : RoleEnum::Student]);

    return $user->createToken('auth-token')->plainTextToken;
}

function currentMonthDate(int $day = 15): string
{
    return now()->startOfMonth()->addDays(min($day, 27) - 1)->toDateString();
}

test('guests cannot access public holidays', function (): void {
    $holiday = PublicHoliday::factory()->create();

    $this->getJson('/settings/public-holidays')->assertUnauthorized();
    $this->postJson('/settings/public-holidays', [])->assertUnauthorized();
    $this->putJson("/settings/public-holidays/{$holiday->id}", [])->assertUnauthorized();
    $this->deleteJson("/settings/public-holidays/{$holiday->id}")->assertUnauthorized();
});

test('non-admin users are forbidden from public holidays', function (): void {
    $token = publicHolidayToken('student');
    $holiday = PublicHoliday::factory()->create();

    $this->withToken($token)->getJson('/settings/public-holidays')->assertForbidden();
    $this->withToken($token)->postJson('/settings/public-holidays', [])->assertForbidden();
    $this->withToken($token)->putJson("/settings/public-holidays/{$holiday->id}", [])->assertForbidden();
    $this->withToken($token)->deleteJson("/settings/public-holidays/{$holiday->id}")->assertForbidden();
});

test('admin lists current month holidays ordered by date without pagination', function (): void {
    $token = publicHolidayToken();
    $month = now()->format('m-Y');

    PublicHoliday::factory()->create(['name' => 'Later Day', 'date' => currentMonthDate(20)]);
    PublicHoliday::factory()->create(['name' => 'Earlier Day', 'date' => currentMonthDate(5)]);
    PublicHoliday::factory()->create(['name' => 'Other Month', 'date' => now()->addMonth()->startOfMonth()->toDateString()]);

    $this->withToken($token)->getJson('/settings/public-holidays')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Earlier Day')
        ->assertJsonPath('data.1.name', 'Later Day')
        ->assertJsonMissingPath('meta');

    $this->withToken($token)->getJson("/settings/public-holidays?state={$month}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('admin filters public holidays by state month', function (): void {
    $token = publicHolidayToken();

    PublicHoliday::factory()->create(['name' => 'Independence Day', 'date' => '2026-08-17']);
    PublicHoliday::factory()->create(['name' => 'Christmas Day', 'date' => '2026-12-25']);

    $this->withToken($token)->getJson('/settings/public-holidays?state=08-2026')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', '2026-08-17');

    $this->withToken($token)->getJson('/settings/public-holidays?state=12-2026')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', '2026-12-25');
});

test('invalid state falls back to the current month', function (): void {
    $token = publicHolidayToken();

    PublicHoliday::factory()->create(['name' => 'Today Holiday', 'date' => currentMonthDate()]);

    foreach (['invalid', '13-2026', '00-2026', '2026-08', '8-2026'] as $state) {
        $this->withToken($token)->getJson("/settings/public-holidays?state={$state}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Today Holiday');
    }
});

test('admin can create a public holiday', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->postJson('/settings/public-holidays', [
        'name' => 'New Year',
        'date' => '2026-01-01',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'New Year')
        ->assertJsonPath('data.date', '2026-01-01')
        ->assertJsonStructure(['data' => ['id', 'name', 'date', 'created_at', 'updated_at']]);

    $stored = PublicHoliday::where('name', 'New Year')->firstOrFail();

    expect($stored->date->toDateString())->toBe('2026-01-01');
});

test('public holidays reject duplicate dates', function (): void {
    $token = publicHolidayToken();

    PublicHoliday::factory()->create(['name' => 'New Year', 'date' => '2026-01-01']);

    $this->withToken($token)->postJson('/settings/public-holidays', [
        'name' => 'Second New Year',
        'date' => '2026-01-01',
    ])->assertUnprocessable();

    $other = PublicHoliday::factory()->create(['name' => 'Other Day', 'date' => '2026-12-25']);
    $holiday = PublicHoliday::whereDate('date', '2026-01-01')->firstOrFail();

    $this->withToken($token)->putJson("/settings/public-holidays/{$other->id}", [
        'date' => '2026-01-01',
    ])->assertUnprocessable();

    $this->withToken($token)->putJson("/settings/public-holidays/{$holiday->id}", [
        'name' => 'Renamed Year',
    ])->assertOk()->assertJsonPath('data.name', 'Renamed Year');
});

test('public holidays reject invalid dates', function (): void {
    $token = publicHolidayToken();

    $this->withToken($token)->postJson('/settings/public-holidays', [
        'name' => 'Impossible Day',
        'date' => '2026-02-30',
    ])->assertUnprocessable();

    $this->withToken($token)->postJson('/settings/public-holidays', [
        'name' => 'Wrong Format',
        'date' => '30-02-2026',
    ])->assertUnprocessable();
});

test('admin can update a public holiday by id', function (): void {
    $token = publicHolidayToken();
    $holiday = PublicHoliday::factory()->create(['name' => 'Old Name', 'date' => '2026-08-17']);

    $this->withToken($token)->putJson("/settings/public-holidays/{$holiday->id}", [
        'name' => 'Independence Day',
        'date' => '2026-08-18',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $holiday->id)
        ->assertJsonPath('data.name', 'Independence Day')
        ->assertJsonPath('data.date', '2026-08-18');

    $this->withToken($token)->putJson('/settings/public-holidays/999999', [
        'name' => 'Missing',
    ])->assertNotFound();
});

test('admin can delete a public holiday by id', function (): void {
    $token = publicHolidayToken();
    $holiday = PublicHoliday::factory()->create();

    $this->withToken($token)->deleteJson("/settings/public-holidays/{$holiday->id}")->assertOk();

    $this->assertDatabaseMissing('public_holidays', ['id' => $holiday->id]);

    $this->withToken($token)->deleteJson("/settings/public-holidays/{$holiday->id}")->assertNotFound();
});
