<?php

namespace App\Services\AI;

use App\Contracts\ChatAIProviderInterface;

/**
 * Fallback AI provider used when OpenAI is unavailable or not configured.
 * Returns neutral/default responses to keep the system functional.
 */
class FallbackAIProvider implements ChatAIProviderInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{sentiment_score: float, detected_emotion: string, confidence: float, ai_reply: string, raw_response: array<string, mixed>}
     */
    public function analyze(string $message, array $context = []): array
    {
        return [
            'sentiment_score' => 0.0,
            'detected_emotion' => 'neutral',
            'confidence' => 0.0,
            'ai_reply' => 'Terima kasih telah berbagi. Saya di sini untuk mendengarkan dan mendukung Anda.',
            'raw_response' => ['provider' => 'fallback'],
        ];
    }

    /**
     * @return array{stress_level: float, detected_emotion: string, confidence_score: float, raw_analysis: array<string, mixed>}
     */
    public function analyzeVoice(string $audioPath): array
    {
        return [
            'stress_level' => 50.0,
            'detected_emotion' => 'neutral',
            'confidence_score' => 0.0,
            'raw_analysis' => ['provider' => 'fallback'],
        ];
    }
}
