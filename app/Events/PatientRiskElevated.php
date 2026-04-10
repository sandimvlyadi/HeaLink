<?php

namespace App\Events;

use App\Models\MentalStatusLog;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientRiskElevated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $patient,
        public readonly MentalStatusLog $mentalStatusLog,
    ) {}

    /**
     * Broadcast payload sent to connected doctor clients.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'patient_uuid' => $this->patient->uuid,
            'patient_name' => $this->patient->name,
            'risk_level' => $this->mentalStatusLog->risk_level,
            'risk_score' => $this->mentalStatusLog->risk_score,
            'summary_note' => $this->mentalStatusLog->summary_note,
            'occurred_at' => $this->mentalStatusLog->created_at?->toIso8601String(),
        ];
    }

    /**
     * Broadcast on all medic private channels so every available doctor receives the alert.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        // Load active medics
        $channels = User::where('role', 'medic')
            ->where('is_active', true)
            ->pluck('uuid')
            ->map(fn (string $uuid) => new PrivateChannel("doctor.{$uuid}"))
            ->all();

        return empty($channels) ? [new PrivateChannel('doctors')] : $channels;
    }
}
