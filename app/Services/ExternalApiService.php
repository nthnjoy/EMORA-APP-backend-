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

    /**
     * Mengambil profil lengkap mahasiswa dari CIS API.
     */
    public function getMahasiswaProfile($identifier, $token = null)
    {
        $baseUrl = rtrim((string) env('API_URL', 'https://cis-dev.del.ac.id/api'), '/');

        try {
            $request = Http::timeout(15)->withoutVerifying()->acceptJson();
            if ($token) {
                $request->withToken($token);
            }

            $response = $request->get("{$baseUrl}/library-api/get-student-by-nim", [
                'nim' => $identifier
            ]);
            
            $data = $response->json();
            if ($response->successful() && isset($data['data']) && isset($data['data']['nim'])) {
                return $data['data'];
            }
            
            $responseUsername = $request->get("{$baseUrl}/library-api/mahasiswa", [
                'username' => $identifier,
                'limit' => 1
            ]);
            
            $dataUsername = $responseUsername->json();
            if ($responseUsername->successful() && isset($dataUsername['data']['mahasiswa'][0])) {
                $profile = $dataUsername['data']['mahasiswa'][0];
                return [
                    'nim' => $profile['nim'] ?? null,
                    'nama' => $profile['nama'] ?? null,
                    'email' => $profile['email'] ?? null,
                    'prodi' => $profile['prodi_name'] ?? $profile['prodi'] ?? null,
                    'tahun_masuk' => $profile['angkatan'] ?? null,
                    'jenis_kelamin' => null,
                    'asrama' => $profile['asrama'] ?? null,
                    'user_name' => $profile['user_name'] ?? null,
                ];
            }

            $responseNim = $request->get("{$baseUrl}/library-api/mahasiswa", [
                'nim' => $identifier,
                'limit' => 1
            ]);
            
            $dataNim = $responseNim->json();
            if ($responseNim->successful() && isset($dataNim['data']['mahasiswa'][0])) {
                $profile = $dataNim['data']['mahasiswa'][0];
                return [
                    'nim' => $profile['nim'] ?? null,
                    'nama' => $profile['nama'] ?? null,
                    'email' => $profile['email'] ?? null,
                    'prodi' => $profile['prodi_name'] ?? $profile['prodi'] ?? null,
                    'tahun_masuk' => $profile['angkatan'] ?? null,
                    'jenis_kelamin' => null,
                    'asrama' => $profile['asrama'] ?? null,
                    'user_name' => $profile['user_name'] ?? null,
                ];
            }
            
            return null;
        } catch (Throwable $e) {
            Log::error("Koneksi CIS getMahasiswaProfile Gagal: " . $e->getMessage());
            return null;
        }
    }
}