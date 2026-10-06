<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function presenceSetupHelper(?Carbon $at = null): array
{
    $at ??= Carbon::parse('2026-10-06 08:10:00');
    Carbon::setTestNow($at);
    Cache::flush();
    $year = AcademicYear::factory()->active()->create();
    $tUser = User::factory()->teacher()->create();
    $teacher = Teacher::factory()->create(['user_id' => $tUser->id]);
    $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
    $day = strtolower($at->format('l'));
    $schedule = ClassSchedule::create([
        'classroom_id' => $classroom->id, 'day' => $day, 'period' => 0,
        'start_time' => '08:00:00', 'end_time' => '09:30:00', 'teacher_id' => $teacher->id,
    ]);
    $sUser = User::factory()->student()->create();
    $student = Student::factory()->create(['user_id' => $sUser->id]);
    $classroom->students()->sync([$student->id]);

    return [$tUser, $teacher, $sUser, $student, $classroom, $schedule, $at];
}
