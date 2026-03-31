<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoodJournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'emoji'        => $this->emoji,
            'mood'         => $this->mood,
            'note'         => $this->note,
            'journal_date' => $this->journal_date?->toDateString(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
