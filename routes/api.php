<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

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
