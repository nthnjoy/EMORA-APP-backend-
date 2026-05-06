<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mood;
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

    public function index(Request $request)
    {
        $user = $request->user();

        $moods = Mood::query()
            ->where('user_id', (string) $user->getKey())
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();

        // Ambil semua feeling terkait
        $moodIds = $moods->pluck('_id')->map(fn($id) => (string) $id)->toArray();
        $feelings = \App\Models\Feeling::whereIn('mood_id', $moodIds)->get()->keyBy('mood_id');

        $data = $moods->map(function(Mood $mood) use ($feelings) {
            $feeling = $feelings[(string) $mood->getKey()] ?? null;
            return $this->transformMood($mood, $feeling);
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

        // Debug log for checking incoming data
        \Log::info('Incoming Mood Request:', ['user' => $user->id, 'payload' => $payload]);
        error_log("Incoming Mood: " . json_encode($payload));

        try {
            $mood = new Mood();
            $mood->user_id = (string) $user->getKey();
            $mood->nim = $user->nim ?? '';
            $mood->username = (string) ($user->username ?? '');
            $mood->mood_name = $payload['mood_label'];
            $mood->mood_code = strtoupper($payload['mood_label']);
            $mood->mood_id = $payload['emosi_kode'];
            $mood->recorded_at = $payload['recorded_at'] ?? now();
            $mood->save();
            
            $feelingModel = null;
            if (!empty($payload['perasaan'])) {
                $feelingModel = new \App\Models\Feeling();
                $feelingModel->user_id = (string) $user->getKey();
                $feelingModel->nim = $user->nim ?? '';
                $feelingModel->username = (string) ($user->username ?? '');
                $feelingModel->mood_id = (string) $mood->getKey();
                $feelingModel->feeling_name = $payload['perasaan'];
                $feelingModel->feeling_code = strtoupper($payload['perasaan']);
                $feelingModel->feeling_id = $this->feelingCodeMap[$payload['perasaan']] ?? 0;
                $feelingModel->recorded_at = $payload['recorded_at'] ?? now();
                $feelingModel->save();
                error_log("Feeling Saved to feelings ID: " . $feelingModel->getKey());
            }

            error_log("Mood Saved Successfully to " . $mood->getTable() . " ID: " . $mood->getKey());

            return response()->json([
                'success' => true,
                'message' => 'Mood dan Perasaan berhasil disimpan',
                'data' => $this->transformMood($mood, $feelingModel),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Mood Save Error: ' . $e->getMessage());
            error_log("Mood Save Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan mood ke database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $mood = Mood::query()
            ->where('_id', $id)
            ->where('user_id', (string) $user->getKey())
            ->first();

        if (! $mood) {
            return response()->json([
                'success' => false,
                'message' => 'Data mood tidak ditemukan',
            ], 404);
        }

        $payload = $request->validate([
            'mood_label' => ['sometimes', 'string', 'max:50'],
            'perasaan' => ['nullable', 'string', 'max:100'],
            'emosi_kode' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('mood_label', $payload)) {
            $mood->mood_name = $payload['mood_label'];
            $mood->mood_code = strtoupper($payload['mood_label']);
        }
        if (array_key_exists('emosi_kode', $payload)) {
            $mood->mood_id = $payload['emosi_kode'];
        }
        if (array_key_exists('recorded_at', $payload)) {
            $mood->recorded_at = $payload['recorded_at'];
        }
        $mood->save();

        return response()->json([
            'success' => true,
            'message' => 'Mood berhasil diperbarui',
            'data' => $this->transformMood($mood),
        ]);
    }

    private function transformMood(Mood $mood, $feeling = null): array
    {
        // Jika parameter feeling tidak di-passing, kita coba fetch dari collection feelings (fallback)
        // Note: property perasaan lama (di collection mood) di-fallback juga agar data lama tetap muncul
        if (!$feeling) {
            $feeling = \App\Models\Feeling::where('mood_id', (string) $mood->getKey())->first();
        }

        $perasaanVal = $feeling ? ($feeling->feeling_name ?? $feeling->perasaan) : ($mood->perasaan ?? null);

        return [
            'id' => (string) $mood->getKey(),
            'user_id' => (string) ($mood->user_id ?? ''),
            'nim' => (string) ($mood->nim ?? ''),
            'username' => (string) ($mood->username ?? ''),
            'mood_label' => (string) ($mood->mood_name ?? $mood->mood_label ?? ''),
            'perasaan' => $perasaanVal,
            'emosi_kode' => (int) ($mood->mood_id ?? $mood->emosi_kode ?? 0),
            'recorded_at' => $mood->recorded_at?->toISOString(),
            'created_at' => $mood->created_at?->toISOString(),
            'updated_at' => $mood->updated_at?->toISOString(),
        ];
    }
}
