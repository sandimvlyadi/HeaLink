<?php

namespace App\Listeners;

use App\Events\NewSentimentAlert;
use App\Events\PatientRiskElevated;
use App\Events\VitalDataSynced;
use App\Services\RiskScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TriggerRiskAssessment implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly RiskScoringService $riskService) {}

    /**
     * Handle VitalDataSynced and NewSentimentAlert events.
     * Both trigger a full risk score recalculation for the affected user.
     */
    public function handle(VitalDataSynced|NewSentimentAlert $event): void
    {
        $user = $event instanceof VitalDataSynced
            ? $event->user
            : $event->chatHistory->user;

        $mentalStatusLog = $this->riskService->calculateAndPersist($user);

        // Fire PatientRiskElevated if risk level is high or critical
        if (in_array($mentalStatusLog->risk_level, ['high', 'critical'], true)) {
            PatientRiskElevated::dispatch($user, $mentalStatusLog);
        }
    }
}
