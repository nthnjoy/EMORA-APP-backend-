<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        
        $payload = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $story = Story::create([
            'user_id' => (string) $user->getKey(),
            'username' => (string) ($user->username ?? ''),
            'content' => $payload['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cerita kamu berhasil dikirim.',
            'data' => [
                'id' => (string) $story->getKey(),
                'user_id' => $story->user_id,
                'username' => $story->username,
                'content' => $story->content,
                'created_at' => $story->created_at?->toISOString(),
                'updated_at' => $story->updated_at?->toISOString(),
            ],
        ], 201);
    }
}
