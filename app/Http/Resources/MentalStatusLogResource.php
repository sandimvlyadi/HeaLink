<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentalStatusLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                 => $this->uuid,
            'risk_level'           => $this->risk_level,
            'risk_score'           => $this->risk_score,
            'detected_emotion'     => $this->detected_emotion,
            'summary_note'         => $this->summary_note,
            'contributing_factors' => $this->contributing_factors,
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
