<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'message' => $this->message,
            'sender_type' => $this->sender_type,
            'sentiment_score' => $this->sentiment_score,
            'detected_emotion' => $this->detected_emotion,
            'is_flagged' => $this->is_flagged,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
