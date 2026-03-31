<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentalStatusLog extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'user_id',
        'risk_level',
        'detected_emotion',
        'summary_note',
        'contributing_factors',
        'risk_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contributing_factors' => 'array',
            'risk_score'           => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
