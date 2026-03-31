<?php

namespace App\Events;

use App\Models\ChatHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user's chat message has a very negative sentiment score (< -0.5).
 * Listened to by TriggerRiskAssessment and SendDoctorNotification.
 */
class NewSentimentAlert
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $analysisResult
     */
    public function __construct(
        public readonly ChatHistory $chatHistory,
        public readonly array $analysisResult,
    ) {}
}

