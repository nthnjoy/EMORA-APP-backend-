<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Endpoint untuk chat/konsultasi AI.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'emotion' => 'nullable|string',
        ]);

        $user = Auth::user();
        $nim = $user->nim ?? $user->username ?? 'Guest';

        $response = $this->aiService.getChatResponse($nim, $request->message, $request->emotion);

        if (isset($response['status']) && $response['status'] === 'success') {
            return response()->json([
                'success' => true,
                'reply' => $response['reply'] ?? 'Maaf, saya tidak mengerti.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Terjadi kesalahan pada AI Engine.',
        ], 500);
    }

    /**
     * Endpoint untuk mendapatkan rekomendasi quote.
     */
    public function recommend(Request $request)
    {
        $user = Auth::user();
        $nim = $user->nim ?? $user->username ?? 'Guest';

        $mood = $request->query('mood');
        $feeling = $request->query('feeling');

        $response = $this->aiService.getRecommendation($nim, $mood, $feeling);

        return response()->json($response);
    }

    /**
     * Health check untuk AI Engine.
     */
    public function status()
    {
        $isOnline = $this->aiService.ping();
        return response()->json([
            'success' => true,
            'ai_engine_online' => $isOnline,
        ]);
    }
}
