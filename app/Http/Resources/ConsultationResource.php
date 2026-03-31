<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'status'       => $this->status,
            'notes'        => $this->notes,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'started_at'   => $this->started_at?->toIso8601String(),
            'ended_at'     => $this->ended_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'patient'      => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'medic'        => $this->whenLoaded('medic', fn () => new UserResource($this->medic)),
        ];
    }
}
