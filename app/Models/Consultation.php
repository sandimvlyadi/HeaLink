<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Consultation extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'medic_id',
        'session_token',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Boot the model — auto-generate session_token.
     */
    protected static function booted(): void
    {
        static::creating(function (Consultation $consultation): void {
            if (empty($consultation->session_token)) {
                $consultation->session_token = Str::random(128);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function medic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medic_id');
    }

    public function facialEmotionLogs(): HasMany
    {
        return $this->hasMany(FacialEmotionLog::class);
    }
}
