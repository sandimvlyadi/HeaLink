<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RiskScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateDailyRiskReportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct() {}

    /**
     * Execute the job: recalculate risk score for all active patients.
     */
    public function handle(RiskScoringService $riskService): void
    {
        User::where('role', 'patient')
            ->where('is_active', true)
            ->with(['latestWearable', 'latestScreening'])
            ->chunkById(50, function ($patients) use ($riskService) {
                foreach ($patients as $patient) {
                    $riskService->calculateAndPersist($patient);
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
