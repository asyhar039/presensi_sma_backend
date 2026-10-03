<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\DutyTeacher;
use App\Models\Teacher;
use Database\Seeders\AcademicYear20262027Seeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2026-2027 seeder resolves json refs into real relations', function (): void {
    $this->seed([SubjectSeeder::class, AcademicYear20262027Seeder::class]);

    expect(AcademicYear::count())->toBe(2)
        ->and(Teacher::count())->toBe(58)
        ->and(Classroom::count())->toBe(54)
        ->and(DutyTeacher::count())->toBe(98)
        ->and(ClassSchedule::count())->toBeGreaterThan(1000)
        ->and(ClassSchedule::whereNull('teacher_id')->count())->toBe(0)
        ->and(ClassSchedule::whereNotNull('teacher_id')->whereNull('subject_id')->count())->toBe(0);

    $schedule = ClassSchedule::with(['classroom', 'teacher.user', 'subject'])->first();
    expect($schedule->classroom->academic_year_id)->toBe($schedule->academic_year_id)
        ->and($schedule->teacher->user->role->value)->toBe('teacher');
});
