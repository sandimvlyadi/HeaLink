<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WearableData extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'user_id',
        'hrv_score',
        'heart_rate',
        'stress_index',
        'device_type',
        'is_simulated',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hrv_score'    => 'decimal:2',
            'stress_index' => 'decimal:2',
            'heart_rate'   => 'integer',
            'is_simulated' => 'boolean',
            'recorded_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
