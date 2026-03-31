<?php

namespace App\Listeners;

use App\Events\PatientRiskElevated;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDoctorNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Handle PatientRiskElevated — notify all active doctors.
     */
    public function handle(PatientRiskElevated $event): void
    {
        $riskLevel = $event->mentalStatusLog->risk_level;
        $patientName = $event->patient->name;

        $title = match ($riskLevel) {
            'critical' => "\u26a0\ufe0f KRITIS: {$patientName} membutuhkan perhatian segera",
            'high'     => "\u{1F534} Risiko Tinggi: {$patientName}",
            default    => "Peringatan Risiko: {$patientName}",
        };

        $message = $event->mentalStatusLog->summary_note
            ?? "Skor risiko pasien {$patientName}: {$event->mentalStatusLog->risk_score}.";

        $actionData = [
            'type'         => 'patient_risk',
            'patient_uuid' => $event->patient->uuid,
            'risk_level'   => $riskLevel,
        ];

        User::where('role', 'medic')
            ->where('is_active', true)
            ->each(fn (User $medic) => $this->notificationService->notify(
                $medic,
                $title,
                $message,
                $riskLevel === 'critical' ? 'critical' : 'warning',
                $actionData,
            ));
    }
}

