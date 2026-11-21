<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherEvaluationController;
use App\Http\Controllers\Api\NotificationController;
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

    // Teacher evaluations
    Route::get('/teacher-ratings/today', [TeacherEvaluationController::class, 'today']);
    Route::get('/teacher-ratings/options', [TeacherEvaluationController::class, 'options']);
    Route::post('/teacher-ratings', [TeacherEvaluationController::class, 'store']);
    Route::get('/teacher-ratings/history', [TeacherEvaluationController::class, 'history']);

    // Attendance
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/device-token', [NotificationController::class, 'registerDeviceToken']);
    Route::delete('/notifications/device-token', [NotificationController::class, 'removeDeviceToken']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});
