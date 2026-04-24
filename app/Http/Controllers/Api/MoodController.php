<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mood;
use Illuminate\Http\Request;

class MoodController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $moods = Mood::query()
            ->where('user_id', (string) $user->getKey())
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Mood $mood) => $this->transformMood($mood))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $moods,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $payload = $request->validate([
            'mood_label' => ['required', 'string', 'max:50'],
            'perasaan' => ['nullable', 'string', 'max:100'],
            'emosi_kode' => ['required', 'integer', 'min:1', 'max:10'],
            'title' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $mood = Mood::create([
            'user_id' => (string) $user->getKey(),
            'username' => (string) ($user->username ?? ''),
            'mood_label' => $payload['mood_label'],
            'perasaan' => $payload['perasaan'] ?? null,
            'emosi_kode' => $payload['emosi_kode'],
            'title' => $payload['title'] ?? null,
            'note' => $payload['note'] ?? null,
            'recorded_at' => $payload['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mood berhasil disimpan',
            'data' => $this->transformMood($mood),
        ], 201);
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
            'title' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $mood->fill($payload);
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

    private function transformMood(Mood $mood): array
    {
        return [
            'id' => (string) $mood->getKey(),
            'user_id' => (string) ($mood->user_id ?? ''),
            'username' => (string) ($mood->username ?? ''),
            'mood_label' => (string) ($mood->mood_label ?? ''),
            'perasaan' => $mood->perasaan,
            'emosi_kode' => (int) ($mood->emosi_kode ?? 0),
            'title' => $mood->title,
            'note' => $mood->note,
            'recorded_at' => $mood->recorded_at?->toISOString(),
            'created_at' => $mood->created_at?->toISOString(),
            'updated_at' => $mood->updated_at?->toISOString(),
        ];
    }
}
