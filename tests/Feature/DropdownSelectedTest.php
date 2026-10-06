<?php

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dropdownSelectedToken(): string
{
    return User::factory()->admin()->create()->createToken('auth-token')->plainTextToken;
}

test('dropdown selected returns only requested ids without meta', function (): void {
    $token = dropdownSelectedToken();
    $years = AcademicYear::factory()->count(3)->create();
    $wanted = $years->take(2)->pluck('id')->all();

    $this->withToken($token)->getJson('/academic-years/dropdown/selected?active_ids[]='.$wanted[0].'&active_ids[]='.$wanted[1])
        ->assertOk()
        ->assertJsonMissing(['meta' => []])
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['value' => $wanted[0]])
        ->assertJsonFragment(['value' => $wanted[1]]);

    $teachers = Teacher::factory()->count(2)->create();
    $this->withToken($token)->getJson('/teachers/dropdown/selected?active_ids[]='.$teachers[0]->id)
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonFragment(['value' => $teachers[0]->id, 'label' => $teachers[0]->user->name]);

    $students = Student::factory()->count(2)->create();
    $this->withToken($token)->getJson('/students/dropdown/selected?active_ids[]='.$students[1]->id)
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonFragment(['value' => $students[1]->id]);

    $this->withToken($token)->getJson('/teachers/dropdown/selected')
        ->assertOk()->assertJsonCount(0, 'data');
});
