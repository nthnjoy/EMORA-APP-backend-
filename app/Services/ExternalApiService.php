<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalApiService
{
    /**
     * Melakukan autentikasi ke API External CIS Del.
     */
    public function authenticateCis($username, $password)
    {
        $baseUrl = rtrim((string) env('API_URL', 'https://cis-dev.del.ac.id/api'), '/');

        try {
            return Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->asForm()
                ->post("{$baseUrl}/jwt-api/do-auth", [
                    'username' => $username,
                    'password' => $password,
                ]);
        } catch (Throwable $e) {
            Log::error("Koneksi CIS Gagal: " . $e->getMessage());
            return new class($e->getMessage()) {
                protected $msg;
                public function __construct($msg) { $this->msg = $msg; }
                public function successful() { return false; }
                public function status() { return 503; }
                public function json() { return ['message' => $this->msg]; }
            };
        }
    }
}