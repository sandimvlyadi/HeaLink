<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthScreeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'height_cm'    => $this->height_cm,
            'weight_kg'    => $this->weight_kg,
            'bmi'          => $this->bmi,
            'systolic'     => $this->systolic,
            'diastolic'    => $this->diastolic,
            'phq9_answers' => $this->phq9_answers,
            'phq9_score'   => $this->phq9_score,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
