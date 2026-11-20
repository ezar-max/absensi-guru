<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health / Ping (untuk cek koneksi dari mobile)
Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'app' => 'absensi-guru',
    'time' => now()->toIso8601String(),
]));

// Auth
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Attendance
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
});
