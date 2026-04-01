<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreChatRequest;
use App\Http\Resources\ChatHistoryResource;
use App\Jobs\AnalyzeChatSentimentJob;
use App\Models\ChatHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Send a chat message to the AI.
     *
     * Stores the user message and dispatches `AnalyzeChatSentimentJob` which
     * processes the message asynchronously and stores the AI reply.
     */
    public function store(StoreChatRequest $request): JsonResponse
    {
        $userMessage = ChatHistory::create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
            'sender_type' => 'user',
        ]);

        AnalyzeChatSentimentJob::dispatch($userMessage);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'data' => [
                'user_message' => new ChatHistoryResource($userMessage),
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    /**
     * Get chat message history.
     *
     * Returns a paginated conversation history for the authenticated user
     * ordered by most recent. Includes both user messages and AI replies.
     *
     * @queryParam page int Page number. Example: 1
     */
    public function history(Request $request): JsonResponse
    {
        $messages = $request->user()
            ->chatHistories()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => ChatHistoryResource::collection($messages),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }
}
