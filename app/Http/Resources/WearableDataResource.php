<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WearableDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'hrv_score'    => $this->hrv_score,
            'heart_rate'   => $this->heart_rate,
            'stress_index' => $this->stress_index,
            'device_type'  => $this->device_type,
            'is_simulated' => $this->is_simulated,
            'recorded_at'  => $this->recorded_at?->toIso8601String(),
        ];
    }
}
