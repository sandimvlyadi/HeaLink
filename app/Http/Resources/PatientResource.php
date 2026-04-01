<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'profile' => $this->whenLoaded('profile', fn () => (new UserProfileResource($this->profile))->resolve()),
            'latest_mental_status' => $this->whenLoaded('latestMentalStatus', fn () => $this->latestMentalStatus ? (new MentalStatusLogResource($this->latestMentalStatus))->resolve() : null),
            'latest_wearable' => $this->whenLoaded('latestWearable', fn () => $this->latestWearable ? (new WearableDataResource($this->latestWearable))->resolve() : null),
            'latest_screening' => $this->whenLoaded('latestScreening', fn () => $this->latestScreening ? (new HealthScreeningResource($this->latestScreening))->resolve() : null),
        ];
    }
}
