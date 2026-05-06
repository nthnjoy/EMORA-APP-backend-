<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Mood;
use App\Services\AiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoryController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }
    public function index(Request $request)
    {
        $user = $request->user();
        $nim = $user->nim ?? $user->username ?? 'Unknown';

        $stories = Story::query()
            ->where('nim', $nim) // Use nim instead of user_id to match new schema
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Story $story) => [
                'id' => (string) $story->getKey(),
                'nim' => $story->nim,
                'content' => $story->description ?? '', // Frontend expects content
                'created_at' => $story->created_at?->toISOString(),
                'updated_at' => $story->updated_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $payload = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        // Debug log for checking incoming data
        \Log::info('Incoming Story Request:', ['user' => $user->id, 'payload' => $payload]);
        error_log("Incoming Story: " . json_encode($payload));

        try {
            $nim = $user->nim ?? $user->username ?? 'Unknown';

            // 1. Fetch Mood History (Last 14 days) for AI Predictive
            $recentMoods = Mood::where('user_id', (string) $user->getKey())
                ->where('created_at', '>=', Carbon::now()->subDays(14))
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'mood' => $m->mood_label,
                    'feeling' => $m->perasaan,
                ])
                ->toArray();

            // 2. Determine days since last journal
            $lastJournal = Story::where('user_id', (string) $user->getKey())
                ->orderBy('created_at', 'desc')
                ->first();
            $daysSinceLastJournal = $lastJournal 
                ? Carbon::now()->diffInDays($lastJournal->created_at) 
                : 30; // Default to 30 if new user

            // 3. Call AI Classification
            $aiResult = $this->aiService->classifyText(
                $nim, 
                $payload['content'], 
                $recentMoods, 
                $daysSinceLastJournal
            );

            $story = new Story();
            $story->nim = $nim;
            $story->description = $payload['content']; // Strict match with ML schema

            // Save AI Results if successful
            if (isset($aiResult['status']) && $aiResult['status'] === 'success') {
                $data = $aiResult['data'];
                // Only save AI data if we actually got it
                $story->ai_level = $data['level'] ?? 0;
                $story->ai_label = $data['label'] ?? 'Unknown';
            }

            $story->save();

            error_log("Story Saved to journal_texts with nim: " . $story->nim);

            // 4. Get AI Feedback (Generate Popup Response)
            $aiFeedback = $this->aiService->getChatResponse($nim, $payload['content']);
            $feedbackMessage = $aiFeedback['reply'] ?? 'Cerita kamu sangat berharga. Terima kasih sudah berbagi!';

            return response()->json([
                'success' => true,
                'message' => 'Cerita kamu berhasil dikirim dan dianalisis oleh AI.',
                'ai_feedback' => $feedbackMessage,
                'data' => [
                    'id' => (string) $story->getKey(),
                    'nim' => $story->nim,
                    'content' => $story->description, // Frontend expects content
                    'created_at' => $story->created_at?->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Story Save Error: ' . $e->getMessage());
            error_log("Story Save Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan cerita ke database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
