<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
Route::get('/teachers', [TeacherController::class, 'index']);
Route::post('/teachers', [TeacherController::class, 'store']);
Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);

Route::patch('/teachers/{teacher}/deactivate', [TeacherController::class, 'deactivate']);
Route::patch('/teachers/{teacher}/activate', [TeacherController::class, 'activate']);

});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');