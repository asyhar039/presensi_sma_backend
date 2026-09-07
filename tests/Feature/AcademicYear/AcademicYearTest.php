<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function adminAcademicToken(string $role = 'admin'): string
{
    $user = User::factory()->create();
    $user->assignRole($role === 'admin' ? RoleEnum::Admin->value : RoleEnum::Teacher->value);

    return $user->createToken('auth-token')->plainTextToken;
}

function academicYearPayload(array $overrides = []): array
{
    return array_merge([
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'semester' => 'odd',
        'is_active' => false,
    ], $overrides);
}

test('guests cannot access academic years', function (): void {
    $this->getJson('/academic-years')->assertUnauthorized();
    $this->postJson('/academic-years', [])->assertUnauthorized();
});

test('non-admin users are forbidden from academic years', function (): void {
    $token = adminAcademicToken('teacher');

    $this->withToken($token)->getJson('/academic-years')->assertForbidden();
    $this->withToken($token)->postJson('/academic-years', academicYearPayload())->assertForbidden();
});

test('admin can create an academic year', function (): void {
    $token = adminAcademicToken();

    $response = $this->withToken($token)->postJson('/academic-years', academicYearPayload(['is_active' => true]));

    $response->assertCreated()
        ->assertJsonPath('data.semester', 'odd')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonStructure(['data' => ['id', 'start_date', 'end_date', 'semester', 'is_active']]);
});

test('only one academic year can be active at a time', function (): void {
    $token = adminAcademicToken();

    $this->withToken($token)->postJson('/academic-years', academicYearPayload(['is_active' => true]))->assertCreated();
    $this->withToken($token)->postJson('/academic-years', academicYearPayload([
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'semester' => 'even',
        'is_active' => true,
    ]))->assertCreated();

    expect(AcademicYear::query()->where('is_active', true)->count())->toBe(1)
        ->and(AcademicYear::query()->where('is_active', true)->first()?->semester->value)->toBe('even');
});

test('activating an academic year via update deactivates the others', function (): void {
    $token = adminAcademicToken();
    $first = AcademicYear::factory()->active()->create();
    $second = AcademicYear::factory()->create();

    $this->withToken($token)->putJson("/academic-years/{$second->id}", ['is_active' => true])->assertOk();

    expect($first->refresh()->is_active)->toBeFalse()
        ->and($second->refresh()->is_active)->toBeTrue();
});

test('academic year requires end date after start date', function (): void {
    $token = adminAcademicToken();

    $this->withToken($token)->postJson('/academic-years', academicYearPayload([
        'start_date' => '2026-06-30',
        'end_date' => '2025-07-01',
    ]))->assertUnprocessable()->assertJsonStructure(['data' => ['end_date']]);
});

test('admin can list, show, update and delete academic years', function (): void {
    $token = adminAcademicToken();
    $academicYear = AcademicYear::factory()->create();

    $this->withToken($token)->getJson('/academic-years')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    $this->withToken($token)->getJson("/academic-years/{$academicYear->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $academicYear->id);

    $this->withToken($token)->putJson("/academic-years/{$academicYear->id}", ['semester' => 'even'])
        ->assertOk()
        ->assertJsonPath('data.semester', 'even');

    $this->withToken($token)->deleteJson("/academic-years/{$academicYear->id}")->assertOk();

    $this->assertDatabaseMissing('academic_years', ['id' => $academicYear->id]);
});
