<?php

use App\Http\Controllers\Api\AcademicYear\AcademicYearController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Classroom\ClassroomController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Room\RoomController;
use App\Http\Controllers\Api\Student\StudentController;
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
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('subjects', SubjectController::class);

    Route::put('students/{student}/password', [StudentController::class, 'changePassword'])->name('students.password');
    Route::apiResource('students', StudentController::class);

    Route::put('teachers/{teacher}/password', [TeacherController::class, 'changePassword'])->name('teachers.password');
    Route::apiResource('teachers', TeacherController::class);

    Route::get('teacher-subjects', [TeacherSubjectController::class, 'index'])->name('teacher-subjects.index');
    Route::post('teacher-subjects', [TeacherSubjectController::class, 'store'])->name('teacher-subjects.store');
    Route::delete('teacher-subjects/{teacherSubject}', [TeacherSubjectController::class, 'destroy'])->name('teacher-subjects.destroy');

    Route::put('classrooms/{classroom}/homeroom', [ClassroomController::class, 'assignHomeroom'])->name('classrooms.homeroom.assign');
    Route::delete('classrooms/{classroom}/homeroom', [ClassroomController::class, 'removeHomeroom'])->name('classrooms.homeroom.remove');
    Route::get('classrooms/{classroom}/students', [ClassroomController::class, 'students'])->name('classrooms.students.index');
    Route::post('classrooms/{classroom}/students', [ClassroomController::class, 'syncStudents'])->name('classrooms.students.sync');
    Route::delete('classrooms/{classroom}/students/{student}', [ClassroomController::class, 'removeStudent'])->name('classrooms.students.remove');
    Route::apiResource('classrooms', ClassroomController::class);
});
