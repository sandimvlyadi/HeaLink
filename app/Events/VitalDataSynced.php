<?php

namespace App\Events;

use App\Models\User;
use App\Models\WearableData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a patient syncs new wearable data (HRV, heart rate, stress).
 * Listened to by TriggerRiskAssessment to update the risk score.
 */
class VitalDataSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly WearableData $wearableData,
    ) {}
}

