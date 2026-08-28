<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\MapelController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\DB;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
Route::get('/teachers', [TeacherController::class, 'index']);
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);

Route::get('/teachers/{teacher}/mapels',[TeacherController::class, 'mapels']);
Route::post('/teachers/{teacher}/mapels',[TeacherController::class, 'assignMapel']);
Route::delete('/teachers/{teacher}/mapels/{mapel}',[TeacherController::class, 'removeMapel']);

Route::get('/classes', [SchoolClassController::class, 'index']);
Route::post('/classes', [SchoolClassController::class, 'store']);
Route::get('/classes/{schoolClass}', [SchoolClassController::class, 'show']);
Route::put('/classes/{schoolClass}', [SchoolClassController::class, 'update']);

Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{student}', [StudentController::class, 'show']);
Route::put('/students/{student}', [StudentController::class, 'update']);
Route::delete('/students/{student}', [StudentController::class, 'destroy']);

Route::get('/mapels', [MapelController::class, 'index']);
Route::post('/mapels', [MapelController::class, 'store']);
Route::get('/mapels/{mapel}', [MapelController::class, 'show']);
Route::put('/mapels/{mapel}', [MapelController::class, 'update']);
Route::delete('/mapels/{mapel}', [MapelController::class, 'destroy']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::put('/rooms/{room}', [RoomController::class, 'update']);

Route::get('/schedules', [ScheduleController::class, 'index']);
Route::post('/schedules', [ScheduleController::class, 'store']);
Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);

Route::patch('/teachers/{teacher}/deactivate', [TeacherController::class, 'deactivate']);
Route::patch('/teachers/{teacher}/activate', [TeacherController::class, 'activate']);
Route::patch('/classes/{schoolClass}/deactivate', [SchoolClassController::class, 'deactivate']);
Route::patch('/classes/{schoolClass}/activate', [SchoolClassController::class, 'activate']);
Route::patch('/rooms/{room}/deactivate', [RoomController::class, 'deactivate']);
Route::patch('/rooms/{room}/activate', [RoomController::class, 'activate']);
Route::patch('/schedules/{schedule}/deactivate', [ScheduleController::class, 'deactivate']);
Route::patch('/schedules/{schedule}/activate', [ScheduleController::class, 'activate']);

});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//health check
Route::get('/health', function () {
    try {
        // Memastikan Laravel benar-benar bisa terkoneksi ke database
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
        ], 200);
    } catch (\Exception $e) {
        // Jika DB mati/gagal konek, kembalikan HTTP Status 500
        return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], 500);
    }
});
