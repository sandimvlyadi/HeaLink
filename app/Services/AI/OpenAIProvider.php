<?php

namespace App\Services\AI;

use App\Contracts\ChatAIProviderInterface;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIProvider implements ChatAIProviderInterface
{
    /**
     * Analyze a chat message for sentiment and emotion using OpenAI.
     *
     * @param  array<string, mixed>  $context
     * @return array{sentiment_score: float, detected_emotion: string, confidence: float, ai_reply: string, raw_response: array<string, mixed>}
     */
    public function analyze(string $message, array $context = []): array
    {
        $systemPrompt = <<<'PROMPT'
        You are a mental health AI assistant for HeaLink. Analyze the user's message and respond with:
        1. A supportive reply in Indonesian
        2. The detected emotion (calm, anxious, sad, angry, neutral, happy)
        3. A sentiment score from -1.0 (very negative) to 1.0 (very positive)
        4. A confidence score from 0.0 to 1.0

        Respond ONLY with valid JSON in this format:
        {"ai_reply": "...", "detected_emotion": "...", "sentiment_score": 0.0, "confidence": 0.0}
        PROMPT;

        $response = OpenAI::chat()->create([
            'model'    => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'response_format' => ['type' => 'json_object'],
            'max_tokens'      => 300,
            'temperature'     => 0.3,
        ]);

        $raw = $response->toArray();
        $parsed = json_decode($raw['choices'][0]['message']['content'] ?? '{}', true) ?? [];

        return [
            'sentiment_score'  => (float) ($parsed['sentiment_score'] ?? 0.0),
            'detected_emotion' => (string) ($parsed['detected_emotion'] ?? 'neutral'),
            'confidence'       => (float) ($parsed['confidence'] ?? 0.5),
            'ai_reply'         => (string) ($parsed['ai_reply'] ?? 'Terima kasih telah berbagi.'),
            'raw_response'     => $raw,
        ];
    }

    /**
     * Analyze voice audio — OpenAI Whisper for transcription + GPT for emotion.
     *
     * @return array{stress_level: float, detected_emotion: string, confidence_score: float, raw_analysis: array<string, mixed>}
     */
    public function analyzeVoice(string $audioPath): array
    {
        // Transcribe with Whisper
        $transcription = OpenAI::audio()->transcribe([
            'model' => 'whisper-1',
            'file'  => fopen($audioPath, 'rb'),
        ]);

        $transcribedText = $transcription->text;

        // Analyze sentiment from transcript
        $analysisResult = $this->analyze($transcribedText);

        // Map sentiment to stress level (inverted: negative sentiment = high stress)
        $sentimentScore = $analysisResult['sentiment_score'];
        $stressLevel = round(50 - ($sentimentScore * 50), 2);
        $stressLevel = max(0.0, min(100.0, $stressLevel));

        return [
            'stress_level'     => $stressLevel,
            'detected_emotion' => $analysisResult['detected_emotion'],
            'confidence_score' => $analysisResult['confidence'],
            'raw_analysis'     => [
                'transcript'       => $transcribedText,
                'sentiment_score'  => $sentimentScore,
                'openai_response'  => $analysisResult['raw_response'],
            ],
        ];
    }
}

