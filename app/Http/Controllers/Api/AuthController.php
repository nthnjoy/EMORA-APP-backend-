<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExternalApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * AuthController handles authentication via External CIS API
 * and manages user data in MongoDB Atlas.
 */
class AuthController extends Controller
{
    protected $cisService;

    /**
     * Dependency Injection for ExternalApiService.
     */
    public function __construct(ExternalApiService $cisService)
    {
        $this->cisService = $cisService;
    }

    /**
     * Login Method
     */
    public function login(Request $request)
    {

        try {
            $credentials = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $response = $this->cisService->authenticateCis($credentials['username'], $credentials['password']);
            $cisStatus = (is_object($response) && method_exists($response, 'status'))
                ? (int) $response->status()
                : 503;
            $cisData = (is_object($response) && method_exists($response, 'json'))
                ? (array) $response->json()
                : [];

            $isHttpSuccessful = is_object($response)
                && method_exists($response, 'successful')
                && $response->successful();
            $cisResult = $cisData['result'] ?? null;
            $isCisAuthenticated = $isHttpSuccessful
                && in_array($cisResult, [true, 1, '1', 'true'], true);

            if (! $isCisAuthenticated) {
                $cisErrorMessage = $cisData['error'] ?? $cisData['message'] ?? null;
                if (! is_string($cisErrorMessage) || $cisErrorMessage === '') {
                    $cisErrorMessage = 'Username atau Password CIS salah';
                }
                $httpStatus = $cisStatus >= 500 ? 502 : 401;

                Log::warning('CIS authentication failed', [
                    'username'   => $credentials['username'],
                    'cis_status' => $cisStatus,
                    'cis_body'   => $cisData,
                ]);


                return response()->json([
                    'success'    => false,
                    'message'    => $httpStatus === 401 ? $cisErrorMessage : 'Autentikasi ke CIS gagal',
                    'cis_status' => $cisStatus,
                    'cis_body'   => $cisData,
                ], $httpStatus);
            }
            $cisUser = isset($cisData['user']) && is_array($cisData['user']) ? $cisData['user'] : [];
            
            $cisToken = $cisData['token'] ?? null;
            $profile = $this->cisService->getMahasiswaProfile($credentials['username'], $cisToken);
            
            if ($profile) {
                $cisUser['nim'] = $profile['nim'] ?? $cisUser['nim'] ?? null;
                $cisUser['name'] = $profile['nama'] ?? $cisUser['name'] ?? null;
                $cisUser['email'] = $profile['email'] ?? $cisUser['email'] ?? null;
                $cisUser['prodi'] = $profile['prodi'] ?? $cisUser['prodi'] ?? null;
                $cisUser['angkatan'] = $profile['tahun_masuk'] ?? $profile['angkatan'] ?? $cisUser['angkatan'] ?? null;
                $cisUser['asrama'] = $profile['asrama'] ?? $cisUser['asrama'] ?? null;
                $cisUser['jenis_kelamin'] = $profile['jenis_kelamin'] ?? null;
            }

            $updateData = [
                'name'     => $cisUser['name'] ?? $cisUser['username'] ?? $credentials['username'],
                'password' => Hash::make($credentials['password']),
                'nim'      => $cisUser['nim'] ?? null,
                'email'    => $cisUser['email'] ?? null,
                'prodi'    => $cisUser['prodi'] ?? null,
                'angkatan' => $cisUser['angkatan'] ?? null,
                'asrama'   => $cisUser['asrama'] ?? null,
            ];

            // Hanya update jenis_kelamin jika data dari CIS tidak null
            // Ini mencegah data lokal (yang diisi user di app) terhapus saat login ulang
            if (isset($cisUser['jenis_kelamin']) && $cisUser['jenis_kelamin'] !== null) {
                $updateData['jenis_kelamin'] = $cisUser['jenis_kelamin'];
            }

            try {
                $user = User::updateOrCreate(
                    ['username' => $credentials['username']],
                    $updateData
                );

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success'      => true,
                    'message'      => 'Login Berhasil',
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                    'token_source' => 'sanctum',
                    'user'         => $user,
                ], 200);
            } catch (Throwable $mongoError) {
                Log::warning('Mongo sync/token generation failed, fallback to CIS token', [
                    'username'  => $credentials['username'],
                    'exception' => get_class($mongoError),
                    'error'     => $mongoError->getMessage(),
                ]);

                $cisToken = (isset($cisData['token']) && is_string($cisData['token']) && $cisData['token'] !== '')
                    ? $cisData['token']
                    : null;

                if ($cisToken === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Login CIS berhasil, tetapi token aplikasi gagal dibuat',
                    ], 503);
                }

                return response()->json([
                    'success'      => true,
                    'message'      => 'Login Berhasil',
                    'access_token' => $cisToken,
                    'token_type'   => 'Bearer',
                    'token_source' => 'cis',
                    'user'         => [
                        'username' => $cisUser['username'] ?? $credentials['username'],
                        'name'     => $cisUser['name'] ?? $cisUser['username'] ?? $credentials['username'],
                        'nim'      => $cisUser['nim'] ?? null,
                        'email'    => $cisUser['email'] ?? null,
                        'role'     => $cisUser['role'] ?? null,
                    ],
                ], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ConnectionException $e) {
            Log::error('CIS API Connection Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke CIS API',
            ], 503);
        } catch (Throwable $e) {
            Log::error('Login Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function updatePoints(Request $request)
    {
        try {
            $request->validate([
                'points' => 'required|integer',
            ]);

            $user = $request->user();
            $targetId = $user->_id;

            \Log::info("Update Poin Spesifik User: {$user->username}", [
                'target_id' => (string) $targetId,
                'points' => $request->points
            ]);

            // Gunakan table() yang lebih standar di Laravel untuk MongoDB
            // Pastikan poin tidak pernah negatif
            $affected = \Illuminate\Support\Facades\DB::connection('mongodb')
                ->table('users')
                ->where('_id', $targetId)
                ->update(['point' => max(0, (int) $request->points)]);

            return response()->json([
                'success' => true,
                'message' => 'Poin berhasil diperbarui',
                'affected_rows' => $affected,
                'user' => $user->fresh(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui poin: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mahasiswa(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'result' => 'Ok',
            'data' => [
                'mahasiswa' => [
                    [
                        'dim_id' => $user->_id ?? null,
                        'user_id' => $user->_id ?? null,
                        'user_name' => $user->username,
                        'nim' => $user->nim ?? null,
                        'nama' => $user->name ?? null,
                        'email' => $user->email ?? '',
                        'prodi_id' => 1,
                        'prodi_name' => $user->prodi ?? '',
                        'fakultas' => 'Vokasi',
                        'angkatan' => $user->angkatan ?? '',
                        'status' => 'Aktif',
                        'asrama' => $user->asrama ?? '',
                        'jenis_kelamin' => $user->jenis_kelamin ?? '',
                        'point' => $user->point ?? 0,
                        'purchased_themes' => $user->purchased_themes ?? [],
                        'active_theme' => $user->active_theme ?? 'default',
                    ],
                ],
            ],
        ]);
    }

    public function getMahasiswaByNim($nim)
    {
        $user = User::where('nim', $nim)->orWhere('username', $nim)->first();

        // Jika tidak ada di lokal atau datanya tidak lengkap, tarik dari CIS
        if (!$user || !$user->nim || !$user->prodi || !$user->jenis_kelamin) {
            $profile = $this->cisService->getMahasiswaProfile($nim);
            
            if ($profile) {
                $username = $user ? $user->username : ($profile['user_name'] ?? $nim);
                $updateData = [
                    'name' => $profile['nama'] ?? $profile['name'] ?? $username,
                    'nim' => $profile['nim'] ?? null,
                    'email' => $profile['email'] ?? null,
                    'prodi' => $profile['prodi'] ?? null,
                    'angkatan' => $profile['tahun_masuk'] ?? $profile['angkatan'] ?? null,
                    'asrama' => $profile['asrama'] ?? null,
                    'password' => $user ? $user->password : Hash::make('defaultpassword'),
                ];

                if (isset($profile['jenis_kelamin']) && $profile['jenis_kelamin'] !== null) {
                    $updateData['jenis_kelamin'] = $profile['jenis_kelamin'];
                }

                $user = User::updateOrCreate(
                    ['username' => $username],
                    $updateData
                );
            }
        }

        if (!$user) {
            return response()->json([
                'result' => 'Error',
                'message' => 'Mahasiswa tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'result' => 'Ok',
            'data' => [
                'mahasiswa' => [
                    [
                        'dim_id' => $user->_id ?? 0,
                        'user_id' => $user->_id ?? 0,
                        'user_name' => $user->username,
                        'nim' => $user->nim,
                        'nama' => $user->name,
                        'email' => $user->email ?? '',
                        'prodi_id' => 1,
                        'prodi_name' => $user->prodi ?? '',
                        'fakultas' => 'Vokasi',
                        'angkatan' => $user->angkatan ?? '',
                        'status' => 'Aktif',
                        'asrama' => $user->asrama ?? '',
                        'jenis_kelamin' => $user->jenis_kelamin ?? '',
                        'point' => $user->point ?? 0,
                        'purchased_themes' => $user->purchased_themes ?? [],
                        'active_theme' => $user->active_theme ?? 'default',
                    ]
                ]
            ]
        ]);
    }
    public function buyTheme(Request $request)
    {
        try {
            $request->validate([
                'theme_id' => 'required|string',
                'cost' => 'required|integer|min:0',
            ]);

            $user = $request->user();
            $currentPoints = (int) ($user->point ?? 0);

            if ($currentPoints < $request->cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Poin tidak cukup untuk membeli tema ini.',
                ], 400);
            }

            $purchasedThemes = (array) ($user->purchased_themes ?? []);
            if (in_array($request->theme_id, $purchasedThemes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema sudah dimiliki.',
                ], 400);
            }

            $purchasedThemes[] = $request->theme_id;

            // Enforce points >= 0
            $newPoints = max(0, $currentPoints - $request->cost);

            \Illuminate\Support\Facades\DB::connection('mongodb')
                ->table('users')
                ->where('_id', $user->_id)
                ->update([
                    'point' => $newPoints,
                    'purchased_themes' => $purchasedThemes,
                    'active_theme' => $request->theme_id
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Tema berhasil dibeli!',
                'user' => $user->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membeli tema: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function setActiveTheme(Request $request)
    {
        try {
            $request->validate([
                'theme_id' => 'required|string',
            ]);

            $user = $request->user();
            $purchasedThemes = (array) ($user->purchased_themes ?? []);

            if (!in_array($request->theme_id, $purchasedThemes) && $request->theme_id !== 'default') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema belum dibeli.',
                ], 403);
            }

            \Illuminate\Support\Facades\DB::connection('mongodb')
                ->table('users')
                ->where('_id', $user->_id)
                ->update(['active_theme' => $request->theme_id]);

            return response()->json([
                'success' => true,
                'message' => 'Tema berhasil diterapkan.',
                'user' => $user->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengganti tema: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateGender(Request $request)
    {
        try {
            $request->validate([
                'jenis_kelamin' => 'required|string|in:Laki-laki,Perempuan',
            ]);

            $user = $request->user();
            
            \Illuminate\Support\Facades\DB::connection('mongodb')
                ->table('users')
                ->where('_id', $user->_id)
                ->update(['jenis_kelamin' => $request->jenis_kelamin]);

            return response()->json([
                'success' => true,
                'message' => 'Jenis kelamin berhasil diperbarui',
                'user' => $user->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jenis kelamin: ' . $e->getMessage(),
            ], 500);
        }
    }
}