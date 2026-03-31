<?php

namespace App\Jobs;

use App\Contracts\ChatAIProviderInterface;
use App\Events\NewSentimentAlert;
use App\Models\ChatHistory;
use App\Services\RiskScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AnalyzeChatSentimentJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds for each attempt.
     */
    public int $timeout = 60;

    /**
     * Backoff in seconds between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(public readonly ChatHistory $chatHistory) {}

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, mixed>
     */
    public function middleware(): array
    {
        return [new RateLimited('openai')];
    }

    /**
     * Execute the job.
     */
    public function handle(ChatAIProviderInterface $aiProvider, RiskScoringService $riskService): void
    {
        $user = $this->chatHistory->user;

        // Gather context snapshot at message time
        $context = [
            'hrv'         => $user->latestWearable?->hrv_score,
            'heart_rate'  => $user->latestWearable?->heart_rate,
            'stress_index' => $user->latestWearable?->stress_index,
        ];

        $result = $aiProvider->analyze($this->chatHistory->message, $context);

        // Update the user message with analysis results
        $this->chatHistory->update([
            'sentiment_score'  => $result['sentiment_score'],
            'detected_emotion' => $result['detected_emotion'],
            'context_data'     => $context,
        ]);

        // Update the corresponding AI reply with the AI-generated response
        ChatHistory::create([
            'user_id'          => $user->id,
            'message'          => $result['ai_reply'],
            'sender_type'      => 'ai',
            'sentiment_score'  => null,
            'detected_emotion' => null,
        ]);

        // Fire sentiment alert for very negative messages
        if ($result['sentiment_score'] < -0.5) {
            NewSentimentAlert::dispatch($this->chatHistory, $result);
        }

        // Trigger risk assessment to update mental status log
        $riskService->calculateAndPersist($user);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}

