<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MoodController;
use App\Http\Controllers\Api\StoryController;
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
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/moods', [MoodController::class, 'index']);
    Route::post('/moods', [MoodController::class, 'store']);
    Route::put('/moods/{id}', [MoodController::class, 'update']);
    
    Route::post('/stories', [StoryController::class, 'store']);
});

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laravel API connected',
    ]);
});

Route::get('/test-db', function () {
    try {
        DB::connection('mongodb')->command(['ping' => 1]);

        return response()->json([
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
