<?php

use App\Http\Controllers\Api\AcademicYear\AcademicYearController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Room\RoomController;
use App\Http\Controllers\Api\Subject\SubjectController;
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
});
