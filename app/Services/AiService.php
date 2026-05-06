<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('AI_ENGINE_URL', 'http://127.0.0.1:8001'), '/');
    }

    /**
     * Mengirim pesan chat ke AI (Generate Popup Response).
     */
    public function getChatResponse($nim, $text, $emotion = null)
    {
        try {
            $response = Http::timeout(20)
                ->post("{$this->baseUrl}/api/generate-popup", [
                    'nim' => (string) $nim,
                    'text' => $text,
                    'emotion' => $emotion,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("AI Engine Error (generate-popup): " . $response->body());
            return ['status' => 'error', 'message' => 'AI Engine tidak merespon dengan benar'];
        } catch (Throwable $e) {
            Log::error("Koneksi AI Engine Gagal: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Tidak dapat terhubung ke AI Engine'];
        }
    }

    /**
     * Mengambil rekomendasi quote dari AI.
     */
    public function getRecommendation($nim, $mood = null, $feeling = null)
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/api/recommend", [
                    'nim' => (string) $nim,
                    'mood' => $mood,
                    'feeling' => $feeling,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => 'error', 'message' => 'Gagal mengambil rekomendasi'];
        } catch (Throwable $e) {
            Log::error("Koneksi AI Recommend Gagal: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'AI Engine Offline'];
        }
    }

    /**
     * Melakukan klasifikasi jurnal/teks.
     */
    public function classifyText($nim, $text, $moodHistory = [], $daysSinceLastJournal = 0)
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/api/classify", [
                    'nim' => (string) $nim,
                    'text' => $text,
                    'mood_history' => $moodHistory,
                    'days_since_last_journal' => $daysSinceLastJournal,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => 'error', 'message' => 'Gagal melakukan klasifikasi'];
        } catch (Throwable $e) {
            Log::error("Koneksi AI Classify Gagal: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'AI Engine Offline'];
        }
    }

    /**
     * Cek status koneksi ke AI Engine.
     */
    public function ping()
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/ping");
            return $response->successful();
        } catch (Throwable $e) {
            return false;
        }
    }
}
