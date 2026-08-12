<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\MapelController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
Route::get('/teachers', [TeacherController::class, 'index']);
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);

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

Route::patch('/teachers/{teacher}/deactivate', [TeacherController::class, 'deactivate']);
Route::patch('/teachers/{teacher}/activate', [TeacherController::class, 'activate']);
Route::patch('/classes/{schoolClass}/deactivate', [SchoolClassController::class, 'deactivate']);
Route::patch('/classes/{schoolClass}/activate', [SchoolClassController::class, 'activate']);

});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');