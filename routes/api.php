<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MoodController;
use App\Http\Controllers\Api\ModuleController;
use App\Models\MoodCategory;
use App\Models\FeelingCategory;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });
    Route::get('/mahasiswa', [AuthController::class, 'mahasiswa']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/moods', [MoodController::class, 'index']);
    Route::post('/moods', [MoodController::class, 'store']);
    Route::put('/moods/{id}', [MoodController::class, 'update']);

    Route::get('/stories', [StoryController::class, 'index']);
    Route::post('/stories', [StoryController::class, 'store']);
    Route::get('/modules', [ModuleController::class, 'index']);
    Route::post('/modules/{id}/upload', [ModuleController::class, 'upload']);

    Route::post('/user/update-points', [AuthController::class, 'updatePoints']);
    Route::post('/user/buy-theme', [AuthController::class, 'buyTheme']);
    Route::post('/user/set-active-theme', [AuthController::class, 'setActiveTheme']);
    Route::post('/user/update-gender', [AuthController::class, 'updateGender']);

    // AI Engine Routes
    Route::get('/ai/status', [AiController::class, 'status']);
    Route::post('/ai/chat', [AiController::class, 'chat']);
    Route::get('/ai/recommend', [AiController::class, 'recommend']);

    // Counselor Notification Routes
    Route::get('/counselor/notifications', [NotificationController::class, 'index']);
    Route::post('/counselor/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});

Route::get('/mahasiswa/{nim}', [AuthController::class, 'getMahasiswaByNim']);
Route::get('/student/{nim}', [AuthController::class, 'getMahasiswaByNim']);

Route::get('/modules/view-pdf/{path}', [ModuleController::class, 'viewPdf'])->where('path', '.*');

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laravel API connected',
    ]);
});

Route::get('/test-db', function () {
    try {
        DB::connection('mongodb')->command(['ping' => 1]);

        return respon  ()->json([
            'success' => true,
            'message' => 'Koneksi MongoDB Atlas Sukses!',
        ]);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        $hint = str_contains($error, 'No suitable servers found')
            ? 'MongoDB Atlas tidak bisa dijangkau dari jaringan saat ini. Pastikan IP publik perangkat sudah di-whitelist pada MongoDB Atlas Network Access, lalu cek koneksi firewall/VPN.'
            : 'Periksa konfigurasi DSN MongoDB dan koneksi internet.';

        return response()->json([
            'success' => false,
            'message' => 'Gagal koneksi ke MongoDB',
            'error' => $error,
            'hint' => $hint,
        ], 500);
    }
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});