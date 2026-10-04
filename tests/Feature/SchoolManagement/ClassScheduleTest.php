<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Setting\ScheduleSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scheduleToken(): string
{
    return User::factory()->admin()->create()->createToken('auth-token')->plainTextToken;
}

function seedMondaySchedule(): array
{
    $slots = [
        ['start' => '07:00', 'end' => '07:40'],
        ['start' => '07:40', 'end' => '08:20'],
        ['start' => '08:20', 'end' => '08:40', 'is_break' => true],
        ['start' => '08:40', 'end' => '09:20'],
        ['start' => '09:20', 'end' => '10:00'],
    ];
    app(ScheduleSettingService::class)->updateDay('monday', $slots);

    return ['start' => '07:00', 'end' => '07:40'];
}

test('guests cannot access class schedules', function (): void {
    $this->getJson('/class-schedules?classroom_id=1&day=monday')->assertUnauthorized();
    $this->postJson('/class-schedules', [])->assertUnauthorized();
});

test('admin can use classroom dropdown defaulting to active year and manage schedules', function (): void {
    $token = scheduleToken();
    $year = AcademicYear::factory()->active()->create();
    $other = AcademicYear::factory()->create();
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
    $otherClassroom = Classroom::factory()->create(['academic_year_id' => $other->id, 'name' => 'Other']);
    $teacher = Teacher::factory()->create();
    $slot = seedMondaySchedule();

    $this->withToken($token)->getJson('/classrooms/dropdown')
        ->assertOk()
        ->assertJsonFragment(['value' => $classroom->id])
        ->assertJsonMissing(['value' => $otherClassroom->id]);

    $this->withToken($token)->getJson('/classrooms/dropdown?academic_year_id='.$other->id)
        ->assertOk()->assertJsonFragment(['value' => $otherClassroom->id]);

    $this->withToken($token)->getJson('/classrooms/dropdown/selected?active_ids[]='.$classroom->id)
        ->assertOk()->assertJsonFragment(['value' => $classroom->id]);

    $this->withToken($token)->getJson("/class-schedules?classroom_id={$classroom->id}&day=monday")
        ->assertOk()->assertJsonCount(0, 'data');

    $this->withToken($token)->getJson('/class-schedules?classroom_id='.$classroom->id)
        ->assertUnprocessable();

    $this->withToken($token)->postJson('/class-schedules', [
        'classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0,
        'start_time' => $slot['start'], 'end_time' => $slot['end'], 'teacher_id' => $teacher->id,
    ])->assertCreated()->assertJsonPath('data', null);

    $list = $this->withToken($token)->getJson("/class-schedules?classroom_id={$classroom->id}&day=monday")
        ->assertOk()->assertJsonCount(1, 'data')->json('data');
    expect($list[0]['period'])->toBe(0)->and($list[0]['teacher']['id'])->toBe($teacher->id);

    $this->withToken($token)->postJson('/class-schedules', [
        'classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0,
        'start_time' => $slot['start'], 'end_time' => $slot['end'], 'teacher_id' => $teacher->id,
    ])->assertUnprocessable();

    $this->withToken($token)->postJson('/class-schedules', [
        'classroom_id' => $classroom->id, 'day' => 'saturday', 'period' => 0,
        'start_time' => '07:00', 'end_time' => '07:30',
    ])->assertUnprocessable();

    $this->withToken($token)->postJson('/class-schedules', [
        'classroom_id' => $classroom->id, 'day' => 'monday', 'period' => 0,
        'start_time' => '07:05', 'end_time' => $slot['end'],
    ])->assertUnprocessable();

    $other2 = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'Second']);
    $this->withToken($token)->postJson('/class-schedules', [
        'classroom_id' => $other2->id, 'day' => 'monday', 'period' => 0,
        'start_time' => $slot['start'], 'end_time' => $slot['end'], 'teacher_id' => $teacher->id,
    ])->assertUnprocessable();

    $id = $list[0]['id'];
    $this->withToken($token)->putJson("/class-schedules/{$id}", ['teacher_id' => null])
        ->assertOk()->assertJsonPath('data', null);
    $this->withToken($token)->deleteJson("/class-schedules/{$id}")->assertOk();
    $this->assertDatabaseMissing('class_schedules', ['id' => $id]);
});
