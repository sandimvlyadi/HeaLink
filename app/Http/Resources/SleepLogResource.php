<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SleepLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'duration_minutes' => $this->duration_minutes,
            'quality_score' => $this->quality_score,
            'quality_category' => $this->quality_category,
            'sleep_time' => $this->sleep_time,
            'wake_time' => $this->wake_time,
            'sleep_date' => $this->sleep_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
