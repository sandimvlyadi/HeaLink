<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'gender'      => $this->gender,
            'dob'         => $this->dob?->toDateString(),
            'job'         => $this->job,
            'phone'       => $this->phone,
            'avatar_path' => $this->avatar_path,
            'bio'         => $this->bio,
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
