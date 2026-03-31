<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SleepLog extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'user_id',
        'duration_minutes',
        'quality_score',
        'quality_category',
        'sleep_time',
        'wake_time',
        'sleep_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'quality_score'    => 'decimal:2',
            'sleep_date'       => 'datetime:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
