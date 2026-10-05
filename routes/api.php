<?php

use App\Http\Controllers\Api\AcademicYear\AcademicYearController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Classroom\ClassroomController;
use App\Http\Controllers\Api\ClassSchedule\ClassScheduleController;
use App\Http\Controllers\Api\Homeroom\HomeroomController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Room\RoomController;
use App\Http\Controllers\Api\Setting\PublicHolidaySettingController;
use App\Http\Controllers\Api\Setting\ScheduleSettingController;
use App\Http\Controllers\Api\Setting\SchoolZoneSettingController;
use App\Http\Controllers\Api\Student\StudentController;
use App\Http\Controllers\Api\StudentSpace\StudentSpaceController;
use App\Http\Controllers\Api\Subject\SubjectController;
use App\Http\Controllers\Api\Teacher\TeacherController;
use App\Http\Controllers\Api\TeacherSubject\TeacherSubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API',
        'data' => null,
    ]);
});

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/information', [AuthController::class, 'information'])->name('information');

        Route::prefix('profile')->name('profile.')->group(function (): void {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        });
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('academic-years/dropdown/selected', [AcademicYearController::class, 'selected'])->name('academic-years.dropdown.selected');
    Route::get('academic-years/dropdown', [AcademicYearController::class, 'dropdown'])->name('academic-years.dropdown');
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('subjects', SubjectController::class);

    Route::put('students/{student}/password', [StudentController::class, 'changePassword'])->name('students.password');
    Route::get('students/dropdown/selected', [StudentController::class, 'selected'])->name('students.dropdown.selected');
    Route::get('students/dropdown', [StudentController::class, 'dropdown'])->name('students.dropdown');
    Route::apiResource('students', StudentController::class);

    Route::put('teachers/{teacher}/password', [TeacherController::class, 'changePassword'])->name('teachers.password');
    Route::get('teachers/dropdown/selected', [TeacherController::class, 'selected'])->name('teachers.dropdown.selected');
    Route::get('teachers/dropdown', [TeacherController::class, 'dropdown'])->name('teachers.dropdown');
    Route::apiResource('teachers', TeacherController::class);

    Route::get('teacher-subjects', [TeacherSubjectController::class, 'index'])->name('teacher-subjects.index');
    Route::post('teacher-subjects', [TeacherSubjectController::class, 'store'])->name('teacher-subjects.store');
    Route::delete('teacher-subjects/{teacherSubject}', [TeacherSubjectController::class, 'destroy'])->name('teacher-subjects.destroy');

    Route::get('classrooms/dropdown/selected', [ClassroomController::class, 'selected'])->name('classrooms.dropdown.selected');
    Route::get('classrooms/dropdown', [ClassroomController::class, 'dropdown'])->name('classrooms.dropdown');
    Route::get('class-schedules', [ClassScheduleController::class, 'index'])->name('class-schedules.index');
    Route::post('class-schedules', [ClassScheduleController::class, 'store'])->name('class-schedules.store');
    Route::put('class-schedules/{classSchedule}', [ClassScheduleController::class, 'update'])->name('class-schedules.update');
    Route::delete('class-schedules/{classSchedule}', [ClassScheduleController::class, 'destroy'])->name('class-schedules.destroy');
    Route::put('classrooms/{classroom}/homeroom', [ClassroomController::class, 'assignHomeroom'])->name('classrooms.homeroom.assign');
    Route::delete('classrooms/{classroom}/homeroom', [ClassroomController::class, 'removeHomeroom'])->name('classrooms.homeroom.remove');
    Route::get('classrooms/{classroom}/students', [ClassroomController::class, 'students'])->name('classrooms.students.index');
    Route::post('classrooms/{classroom}/students', [ClassroomController::class, 'syncStudents'])->name('classrooms.students.sync');
    Route::delete('classrooms/{classroom}/students/{student}', [ClassroomController::class, 'removeStudent'])->name('classrooms.students.remove');
    Route::apiResource('classrooms', ClassroomController::class);

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('schedules', [ScheduleSettingController::class, 'index'])->name('schedules.index');
        Route::get('schedules/{day}', [ScheduleSettingController::class, 'show'])->name('schedules.show');
        Route::put('schedules', [ScheduleSettingController::class, 'update'])->name('schedules.update');

        Route::get('public-holidays', [PublicHolidaySettingController::class, 'index'])->name('public-holidays.index');
        Route::post('public-holidays', [PublicHolidaySettingController::class, 'store'])->name('public-holidays.store');
        Route::put('public-holidays/{publicHoliday}', [PublicHolidaySettingController::class, 'update'])->name('public-holidays.update');
        Route::delete('public-holidays/{publicHoliday}', [PublicHolidaySettingController::class, 'destroy'])->name('public-holidays.destroy');

        Route::get('school-zones', [SchoolZoneSettingController::class, 'index'])->name('school-zones.index');
        Route::post('school-zones', [SchoolZoneSettingController::class, 'store'])->name('school-zones.store');
        Route::get('school-zones/{schoolZone}', [SchoolZoneSettingController::class, 'show'])->name('school-zones.show');
        Route::put('school-zones/{schoolZone}', [SchoolZoneSettingController::class, 'update'])->name('school-zones.update');
        Route::patch('school-zones/{schoolZone}/active', [SchoolZoneSettingController::class, 'updateActive'])->name('school-zones.active');
    });
});

Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->name('student.')->group(function (): void {
    Route::get('/information', [StudentSpaceController::class, 'information'])->name('information');
    Route::get('/presence', [StudentSpaceController::class, 'presence'])->name('presence');
    Route::get('/leave-requests', [StudentSpaceController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/{leaveRequest}', [StudentSpaceController::class, 'show'])->name('leave-requests.show');
    Route::post('/leave-requests/sick-leave', [StudentSpaceController::class, 'storeSickLeave'])->name('leave-requests.sick-leave');
    Route::post('/leave-requests/early-out', [StudentSpaceController::class, 'storeEarlyOut'])->name('leave-requests.early-out');
    Route::post('/leave-requests/late-arrival', [StudentSpaceController::class, 'storeLateArrival'])->name('leave-requests.late-arrival');
});

Route::middleware(['auth:sanctum', 'role:teacher'])->prefix('homeroom')->name('homeroom.')->group(function (): void {
    Route::get('/', [HomeroomController::class, 'show'])->name('show');
    Route::get('/students', [HomeroomController::class, 'students'])->name('students.index');
});
