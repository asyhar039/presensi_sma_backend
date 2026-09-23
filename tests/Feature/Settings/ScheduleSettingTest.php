<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scheduleSettingsToken(string $role = 'admin'): string
{
    $user = User::factory()->create(['role' => $role === 'admin' ? RoleEnum::Admin : RoleEnum::Student]);

    return $user->createToken('auth-token')->plainTextToken;
}

function validSchedules(): array
{
    return [
        ['start' => '07:00', 'end' => '07:40'],
        ['start' => '07:40', 'end' => '08:20'],
        ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
        ['start' => '08:40', 'end' => '09:20'],
        ['start' => '09:20', 'end' => '10:00'],
    ];
}

test('guests cannot access day schedules', function (): void {
    $this->getJson('/settings/schedules')->assertUnauthorized();
    $this->putJson('/settings/schedules', ['day' => 'monday', 'schedules' => []])->assertUnauthorized();
});

test('non-admin users are forbidden from day schedules', function (): void {
    $token = scheduleSettingsToken('student');

    $this->withToken($token)->getJson('/settings/schedules')->assertForbidden();
    $this->withToken($token)->putJson('/settings/schedules', ['day' => 'monday', 'schedules' => []])->assertForbidden();
});

test('admin can list all day schedules', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->getJson('/settings/schedules')
        ->assertOk()
        ->assertJsonPath('data.0.day', 'monday')
        ->assertJsonCount(7, 'data');
});

test('admin can upsert and show a day schedule', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'monday',
        'schedules' => validSchedules(),
    ])
        ->assertOk()
        ->assertJsonPath('data.day', 'monday')
        ->assertJsonPath('data.schedules.2.is_break', true);

    $this->withToken($token)->getJson('/settings/schedules/monday')
        ->assertOk()
        ->assertJsonPath('data.schedules.0.start', '07:00')
        ->assertJsonCount(5, 'data.schedules');

    $this->withToken($token)->getJson('/settings/schedules/funday')->assertNotFound();
});

test('day schedule rejects overlapping slots', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => [
            ['start' => '07:00', 'end' => '08:00'],
            ['start' => '07:30', 'end' => '08:30'],
        ],
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules']]);
});

test('day schedule rejects gaps between slots', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => [
            ['start' => '07:00', 'end' => '07:40'],
            ['start' => '08:00', 'end' => '08:40'],
        ],
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules']]);
});

test('day schedule rejects an end time before its start time', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => [
            ['start' => '08:00', 'end' => '07:40'],
        ],
    ])->assertUnprocessable();
});

test('day schedule rejects invalid day and time formats', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'funday',
        'schedules' => validSchedules(),
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['day']]);

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'wednesday',
        'schedules' => [['start' => '7am', 'end' => '08:00']],
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules.0.start']]);
});

test('day schedule allows empty or missing schedules for holidays', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'saturday',
        'schedules' => [],
    ])->assertOk()->assertJsonCount(0, 'data.schedules');

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'sunday',
    ])->assertOk()->assertJsonCount(0, 'data.schedules');
});

test('day schedule rejects unsorted slots without reordering', function (): void {
    $token = scheduleSettingsToken();

    $schedules = validSchedules();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => array_reverse($schedules),
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules']]);
});

test('day schedule requires at least one break and four lessons', function (): void {
    $token = scheduleSettingsToken();

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => [
            ['start' => '07:00', 'end' => '07:40'],
            ['start' => '07:40', 'end' => '08:20'],
            ['start' => '08:20', 'end' => '09:00'],
            ['start' => '09:00', 'end' => '09:40'],
            ['start' => '09:40', 'end' => '10:20'],
        ],
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules']]);

    $this->withToken($token)->putJson('/settings/schedules', [
        'day' => 'tuesday',
        'schedules' => [
            ['start' => '07:00', 'end' => '07:40'],
            ['start' => '07:40', 'end' => '08:20', 'is_break' => true],
            ['start' => '08:20', 'end' => '09:00'],
        ],
    ])->assertUnprocessable()->assertJsonStructure(['data' => ['schedules']]);
});
