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

            try {
                $user = User::updateOrCreate(
                    ['username' => $credentials['username']],
                    [
                        'name'     => $cisUser['name'] ?? $cisUser['username'] ?? $credentials['username'],
                        'password' => Hash::make($credentials['password']),
                        'nim'      => $cisUser['nim'] ?? null,
                    ]
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
}