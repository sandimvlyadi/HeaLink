<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Traits\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPublicId, HasTeams, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'current_team_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function healthScreenings(): HasMany
    {
        return $this->hasMany(HealthScreening::class);
    }

    public function wearableData(): HasMany
    {
        return $this->hasMany(WearableData::class);
    }

    public function sleepLogs(): HasMany
    {
        return $this->hasMany(SleepLog::class);
    }

    public function voiceAnalyses(): HasMany
    {
        return $this->hasMany(VoiceAnalysis::class);
    }

    public function chatHistories(): HasMany
    {
        return $this->hasMany(ChatHistory::class);
    }

    public function mentalStatusLogs(): HasMany
    {
        return $this->hasMany(MentalStatusLog::class);
    }

    public function moodJournals(): HasMany
    {
        return $this->hasMany(MoodJournal::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function consultationsAsPatient(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function consultationsAsMedic(): HasMany
    {
        return $this->hasMany(Consultation::class, 'medic_id');
    }

    // =========================================================================
    // Scoped relationships — query efisien via latestOfMany
    // =========================================================================

    public function latestMentalStatus(): HasOne
    {
        return $this->hasOne(MentalStatusLog::class)->latestOfMany();
    }

    public function latestWearable(): HasOne
    {
        return $this->hasOne(WearableData::class)->latestOfMany('recorded_at');
    }

    public function latestScreening(): HasOne
    {
        return $this->hasOne(HealthScreening::class)->latestOfMany();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeByRole(Builder $query, string $role): void
    {
        $query->where('role', $role);
    }
}
