<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyCheckin;
use App\Models\Mood;
use App\Models\Feeling;
use Illuminate\Http\Request;

class MoodController extends Controller
{
    private $feelingCodeMap = [
        // Senang
        'Gembira' => 1, 'Bangga' => 2, 'Bersyukur' => 3, 'Ceria' => 4,
        // Antusias
        'Semangat' => 5, 'Energik' => 6, 'Kagum' => 7, 'Bergairah' => 8,
        // Netral
        'Biasa Saja' => 9, 'Stabil' => 10, 'Tenang' => 11, 'Santai' => 12,
        // Terkejut
        'Tercengang' => 13, 'Penasaran' => 14, 'Tertarik' => 15, 'Gelagapan' => 16,
        // Sedih
        'Pilu' => 17, 'Depresi' => 18, 'Kesepian' => 19, 'Putus Asa' => 20,
        // Takut
        'Cemas' => 21, 'Khawatir' => 22, 'Panik' => 23, 'Gelisah' => 24,
        // Marah
        'Kesal' => 25, 'Jengkel' => 26, 'Benci' => 27, 'Kecewa' => 28,
    ];

    protected $aiService;

    public function __construct(\App\Services\AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil data dari collection daily_checkins
        $checkins = DailyCheckin::query()
            ->where('user_id', (string) $user->getKey())
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();

        $data = $checkins->map(function(DailyCheckin $checkin) {
            return [
                'id' => (string) $checkin->getKey(),
                'user_id' => (string) ($checkin->user_id ?? ''),
                'nim' => (string) ($checkin->nim ?? ''),
                'username' => (string) ($checkin->username ?? ''),
                'mood_label' => (string) ($checkin->mood_label ?? ''),
                'perasaan' => (string) ($checkin->perasaan ?? ''),
                'emosi_kode' => (int) ($checkin->mood_id ?? 0),
                'recorded_at' => $checkin->recorded_at?->toISOString(),
                'created_at' => $checkin->created_at?->toISOString(),
                'updated_at' => $checkin->updated_at?->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $payload = $request->validate([
            'mood_label' => ['required', 'string', 'max:50'],
            'perasaan' => ['nullable', 'string', 'max:100'],
            'emosi_kode' => ['required', 'integer', 'min:1', 'max:10'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        \Log::info('Incoming Daily Check-in Request:', ['user' => $user->id, 'payload' => $payload]);

        try {
            $checkin = new DailyCheckin();
            $checkin->user_id = (string) $user->getKey();
            $checkin->nim = $user->nim ?? '';
            $checkin->username = (string) ($user->username ?? '');
            
            $checkin->mood_label = $payload['mood_label'];
            $checkin->perasaan = $payload['perasaan'];
            
            // emosi_kode dipetakan ke mood_id
            $checkin->mood_id = $payload['emosi_kode'];
            
            // feeling_id dipetakan dari feelingCodeMap
            if (!empty($payload['perasaan'])) {
                $checkin->feeling_id = $this->feelingCodeMap[$payload['perasaan']] ?? 0;
            }
            
            $checkin->recorded_at = $payload['recorded_at'] ?? now();
            $checkin->save();

            \Log::info("Daily Check-in Saved Successfully. ID: " . $checkin->getKey());

            // 3. Get AI Feedback (Recommender)
            $nim = $user->nim ?? $user->username ?? 'Guest';
            error_log("Calling AI Recommender for NIM: $nim, Mood: " . $payload['mood_label']);
            $aiFeedback = $this->aiService->getRecommendation($nim, $payload['mood_label'], $payload['perasaan'] ?? null);
            error_log("AI Recommender Response: " . json_encode($aiFeedback));
            
            $feedbackMessage = $aiFeedback['quote'] ?? 'Terima kasih sudah berbagi perasaanmu hari ini!';

            return response()->json([
                'success' => true,
                'message' => 'Mood dan Perasaan berhasil disimpan ke Daily Check-ins',
                'ai_feedback' => $feedbackMessage,
                'data' => [
                    'id' => (string) $checkin->getKey(),
                    'mood_label' => $checkin->mood_label,
                    'perasaan' => $checkin->perasaan,
                    'recorded_at' => $checkin->recorded_at?->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Daily Check-in Save Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan mood ke Daily Check-ins.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $checkin = DailyCheckin::query()
            ->where('_id', $id)
            ->where('user_id', (string) $user->getKey())
            ->first();

        if (! $checkin) {
            return response()->json([
                'success' => false,
                'message' => 'Data check-in tidak ditemukan',
            ], 404);
        }

        $payload = $request->validate([
            'mood_label' => ['sometimes', 'string', 'max:50'],
            'perasaan' => ['nullable', 'string', 'max:100'],
            'emosi_kode' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('mood_label', $payload)) {
            $checkin->mood_label = $payload['mood_label'];
        }
        if (array_key_exists('perasaan', $payload)) {
            $checkin->perasaan = $payload['perasaan'];
        }
        if (array_key_exists('emosi_kode', $payload)) {
            $checkin->mood_id = $payload['emosi_kode'];
        }
        if (array_key_exists('recorded_at', $payload)) {
            $checkin->recorded_at = $payload['recorded_at'];
        }
        $checkin->save();

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil diperbarui',
            'data' => [
                'id' => (string) $checkin->getKey(),
                'mood_label' => $checkin->mood_label,
                'perasaan' => $checkin->perasaan,
                'recorded_at' => $checkin->recorded_at?->toISOString(),
            ],
        ]);
    }

}
