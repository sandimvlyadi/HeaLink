<?php

namespace App\Jobs;

use App\Contracts\ChatAIProviderInterface;
use App\Models\VoiceAnalysis;
use App\Services\RiskScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessVoiceAnalysisJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(public readonly VoiceAnalysis $voiceAnalysis) {}

    /**
     * @return array<int, mixed>
     */
    public function middleware(): array
    {
        return [new RateLimited('openai')];
    }

    public function handle(ChatAIProviderInterface $aiProvider, RiskScoringService $riskService): void
    {
        $absolutePath = Storage::path($this->voiceAnalysis->audio_path);

        $result = $aiProvider->analyzeVoice($absolutePath);

        $this->voiceAnalysis->update([
            'stress_level' => $result['stress_level'],
            'detected_emotion' => $result['detected_emotion'],
            'confidence_score' => $result['confidence_score'],
            'raw_analysis' => $result['raw_analysis'],
        ]);

        // Re-calculate risk score to incorporate latest voice analysis data
        $riskService->calculateAndPersist($this->voiceAnalysis->user);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
