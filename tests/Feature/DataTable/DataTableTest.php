<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function dataTableToken(): string
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    return $user->createToken('auth-token')->plainTextToken;
}

test('rooms support search and name sorting with the paginated meta shape', function (): void {
    $token = dataTableToken();
    Room::factory()->create(['name' => 'Alfa Room']);
    Room::factory()->create(['name' => 'Beta Room']);

    $this->withToken($token)->getJson('/rooms?search=Alfa')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alfa Room')
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'total_pages']]);

    $names = $this->withToken($token)->getJson('/rooms?sortBy=name&order=asc')
        ->assertOk()
        ->json('data');

    expect(array_column($names, 'name'))->toBe(['Alfa Room', 'Beta Room']);
});

test('rooms reject unknown sort columns', function (): void {
    $token = dataTableToken();

    $this->withToken($token)->getJson('/rooms?sortBy=password&order=asc')
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['sortBy']]);
});

test('subjects support search and reject invalid pagination', function (): void {
    $token = dataTableToken();
    Subject::factory()->create(['name' => 'Mathematics']);
    Subject::factory()->create(['name' => 'Physics']);

    $this->withToken($token)->getJson('/subjects?search=Math')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mathematics');

    $this->withToken($token)->getJson('/subjects?per_page=999')
        ->assertUnprocessable();
});

test('academic years filter by semester and year without search', function (): void {
    $token = dataTableToken();
    AcademicYear::factory()->create([
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'semester' => 'odd',
    ]);
    AcademicYear::factory()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'semester' => 'even',
    ]);

    $this->withToken($token)->getJson('/academic-years?semester=odd')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.semester', 'odd');

    $this->withToken($token)->getJson('/academic-years?year=2025')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withToken($token)->getJson('/academic-years?semester=invalid')
        ->assertUnprocessable();
});

test('students search by identity number and filter by status and gender', function (): void {
    $token = dataTableToken();
    $match = Student::factory()->for(User::factory(['identity_number' => 'STD000001', 'name' => 'Target Student']))->create([
        'status' => 'active',
        'gender' => 'male',
    ]);
    Student::factory()->create(['status' => 'inactive', 'gender' => 'female']);

    $this->withToken($token)->getJson('/students?search=STD000001')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->withToken($token)->getJson('/students?status=active&gender=male')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->withToken($token)->getJson('/students?status=unknown')
        ->assertUnprocessable();
});

test('teachers filter by employment status and sort by name', function (): void {
    $token = dataTableToken();
    Teacher::factory()->for(User::factory(['name' => 'Zara Teacher']))->create(['employment_status' => 'pns']);
    Teacher::factory()->for(User::factory(['name' => 'Ahmad Teacher']))->create(['employment_status' => 'honorer']);

    $this->withToken($token)->getJson('/teachers?employment_status=pns')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.name', 'Zara Teacher');

    $names = $this->withToken($token)->getJson('/teachers?sortBy=name&order=asc')
        ->assertOk()
        ->json('data');

    expect(array_column(array_column($names, 'user'), 'name'))->toBe(['Ahmad Teacher', 'Zara Teacher']);
});
