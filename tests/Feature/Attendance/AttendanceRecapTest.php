<?php

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\LeaveRequest;
use App\Models\PresenceRecord;
use App\Models\PresenceSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    Cache::flush();
});

test('admin can retrieve attendance summary card metrics', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $year = AcademicYear::factory()->active()->create([
        'odd_start_date' => '2026-07-01',
        'odd_end_date' => '2026-12-31',
    ]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'X MIPA 1']);

    $sUser1 = User::factory()->student()->create(['name' => 'Aditya Pratama', 'identity_number' => '202401001']);
    $student1 = Student::factory()->create(['user_id' => $sUser1->id]);
    $classroom->students()->sync([$student1->id]);

    $tUser = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);
    $subject = Subject::factory()->create(['name' => 'Matematika']);
    $teacher->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);

    $schedule = ClassSchedule::create([
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'day' => 'thursday',
        'period' => 1,
        'start_time' => '07:30:00',
        'end_time' => '09:00:00',
    ]);

    $session = PresenceSession::create([
        'class_schedule_id' => $schedule->id,
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'date' => '2026-10-15',
        'status' => 'closed',
        'total_students' => 1,
    ]);

    PresenceRecord::create([
        'presence_session_id' => $session->id,
        'student_id' => $student1->id,
        'user_id' => $sUser1->id,
        'scanned_at' => '2026-10-15 07:31:00',
        'inside_zone' => true,
    ]);

    $response = $this->getJson(route('attendance.summary', ['classroom_id' => $classroom->id]));

    $response->assertOk()
        ->assertJson([
            'message' => 'Attendance summary retrieved successfully',
            'data' => [
                'total_students' => 1,
                'average_attendance' => 100.0,
                'total_present' => 1,
                'total_permission' => 0,
                'total_sick' => 0,
                'total_absent' => 0,
            ],
        ]);
});

test('admin can retrieve student summaries table with pagination', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $year = AcademicYear::factory()->active()->create([
        'odd_start_date' => '2026-07-01',
        'odd_end_date' => '2026-12-31',
    ]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'X MIPA 1']);

    $sUser1 = User::factory()->student()->create(['name' => 'Aditya Pratama', 'identity_number' => '202401001']);
    $student1 = Student::factory()->create(['user_id' => $sUser1->id]);

    $sUser2 = User::factory()->student()->create(['name' => 'Budi Santoso', 'identity_number' => '202401002']);
    $student2 = Student::factory()->create(['user_id' => $sUser2->id]);

    $classroom->students()->sync([$student1->id, $student2->id]);

    $tUser = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);

    $schedule = ClassSchedule::create([
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'day' => 'thursday',
        'period' => 1,
        'start_time' => '07:30:00',
        'end_time' => '09:00:00',
    ]);

    $session = PresenceSession::create([
        'class_schedule_id' => $schedule->id,
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'date' => '2026-10-15',
        'status' => 'closed',
        'total_students' => 2,
    ]);

    PresenceRecord::create([
        'presence_session_id' => $session->id,
        'student_id' => $student1->id,
        'user_id' => $sUser1->id,
        'scanned_at' => '2026-10-15 07:31:00',
        'inside_zone' => true,
    ]);

    LeaveRequest::create([
        'user_id' => $sUser2->id,
        'type' => LeaveRequestTypeEnum::SickLeave,
        'status' => LeaveRequestStatusEnum::Approved,
        'start_date' => '2026-10-15',
        'end_date' => '2026-10-15',
        'range_date' => ['2026-10-15'],
        'notes' => 'Demam',
    ]);

    $response = $this->getJson(route('attendance.student-summaries', [
        'classroom_id' => $classroom->id,
        'page' => 1,
        'per_page' => 10,
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => [
                    'student_id',
                    'student_name',
                    'student_nis',
                    'present',
                    'permission',
                    'sick',
                    'absent',
                    'total',
                    'attendance_rate',
                ],
            ],
            'meta' => [
                'page',
                'per_page',
                'total',
                'total_pages',
            ],
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2);

    $aditya = collect($data)->firstWhere('student_id', $student1->id);
    expect($aditya['present'])->toBe(1);
    expect($aditya['absent'])->toBe(0);
    expect($aditya['attendance_rate'])->toBe(100.0);

    $budi = collect($data)->firstWhere('student_id', $student2->id);
    expect($budi['present'])->toBe(0);
    expect($budi['sick'])->toBe(1);
    expect($budi['attendance_rate'])->toBe(0.0);
});

test('admin can retrieve student attendance detail records', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $year = AcademicYear::factory()->active()->create([
        'odd_start_date' => '2026-07-01',
        'odd_end_date' => '2026-12-31',
    ]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'X MIPA 1']);

    $sUser = User::factory()->student()->create(['name' => 'Aditya Pratama', 'identity_number' => '202401001']);
    $student = Student::factory()->create(['user_id' => $sUser->id]);
    $classroom->students()->sync([$student->id]);

    $tUser = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);
    $subject = Subject::factory()->create(['name' => 'Matematika Wajib']);
    $teacher->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);

    $schedule = ClassSchedule::create([
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'day' => 'thursday',
        'period' => 1,
        'start_time' => '07:30:00',
        'end_time' => '09:00:00',
    ]);

    $session = PresenceSession::create([
        'class_schedule_id' => $schedule->id,
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
        'date' => '2026-10-15',
        'status' => 'closed',
        'total_students' => 1,
    ]);

    PresenceRecord::create([
        'presence_session_id' => $session->id,
        'student_id' => $student->id,
        'user_id' => $sUser->id,
        'scanned_at' => '2026-10-15 07:31:00',
        'inside_zone' => true,
    ]);

    $response = $this->getJson(route('attendance.student.detail', [
        'student' => $student->id,
        'classroom_id' => $classroom->id,
    ]));

    $response->assertOk()
        ->assertJson([
            'message' => 'Student attendance detail retrieved successfully',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'user' => [
                        'id' => $sUser->id,
                        'identity_number' => '202401001',
                        'name' => 'Aditya Pratama',
                    ],
                ],
                'summary' => [
                    'student_id' => $student->id,
                    'student_name' => 'Aditya Pratama',
                    'student_nis' => '202401001',
                    'present' => 1,
                    'permission' => 0,
                    'sick' => 0,
                    'absent' => 0,
                    'total' => 1,
                    'attendance_rate' => 100.0,
                ],
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'records' => [
                    '*' => [
                        'id',
                        'student_id',
                        'student',
                        'subject_id',
                        'subject_name',
                        'classroom_id',
                        'classroom_name',
                        'date',
                        'time_start',
                        'time_end',
                        'room_name',
                        'status',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
});

test('student cannot access attendance recap endpoints', function (): void {
    $sUser = User::factory()->student()->create();
    Sanctum::actingAs($sUser);

    $this->getJson(route('attendance.summary'))->assertForbidden();
    $this->getJson(route('attendance.student-summaries'))->assertForbidden();
    $this->getJson(route('attendance.student.detail', ['student' => 1]))->assertForbidden();
});

