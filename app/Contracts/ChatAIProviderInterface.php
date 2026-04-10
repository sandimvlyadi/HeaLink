<?php

namespace App\Contracts;

interface ChatAIProviderInterface
{
    /**
     * Analyze a chat message and return sentiment and emotion data.
     *
     * @param  string  $message  The user's chat message
     * @param  array<string, mixed>  $context  Snapshot of user vitals/mood at message time
     * @return array{
     *     sentiment_score: float,
     *     detected_emotion: string,
     *     confidence: float,
     *     ai_reply: string,
     *     raw_response: array<string, mixed>
     * }
     */
    public function analyze(string $message, array $context = []): array;

    /**
     * Analyze an audio file and return stress and emotion data.
     *
     * @param  string  $audioPath  Absolute path or storage key to the audio file
     * @return array{
     *     stress_level: float,
     *     detected_emotion: string,
     *     confidence_score: float,
     *     raw_analysis: array<string, mixed>
     * }
     */
    public function analyzeVoice(string $audioPath): array;
}
