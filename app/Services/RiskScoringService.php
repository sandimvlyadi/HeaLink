<?php

namespace App\Services;

use App\Models\MentalStatusLog;
use App\Models\RiskThreshold;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RiskScoringService
{
    /**
     * Calculate risk score for a user, persist to mental_status_logs, and return the record.
     */
    public function calculateAndPersist(User $user): MentalStatusLog
    {
        /** @var Collection<string, RiskThreshold> $thresholds */
        $thresholds = Cache::remember('risk_thresholds', 3600, fn () => RiskThreshold::where('is_active', true)->get()->keyBy('parameter_name'));

        $factors = $this->gatherFactors($user);
        $score = $this->computeWeightedScore($factors, $thresholds);
        $riskLevel = $this->scoreToLevel($score);

        return MentalStatusLog::create([
            'user_id'              => $user->id,
            'risk_level'           => $riskLevel,
            'risk_score'           => $score,
            'contributing_factors' => $factors,
            'detected_emotion'     => $factors['latest_emotion'] ?? null,
            'summary_note'         => $this->generateSummary($factors, $riskLevel),
        ]);
    }

    /**
     * Gather all available risk factors from user relations.
     *
     * @return array{hrv: float|null, sleep_duration: int|null, sentiment: float|null, phq9: int|null, stress_index: float|null, latest_emotion: string|null}
     */
    public function gatherFactors(User $user): array
    {
        $latestWearable = $user->latestWearable;
        $latestSleep = $user->sleepLogs()->latest('sleep_date')->first();
        $sentiment = $user->chatHistories()->latest()->limit(5)->avg('sentiment_score');
        $latestScreening = $user->latestScreening;
        $latestEmotion = $user->chatHistories()->latest()->value('detected_emotion');

        return [
            'hrv'            => $latestWearable?->hrv_score !== null ? (float) $latestWearable->hrv_score : null,
            'sleep_duration' => $latestSleep?->duration_minutes,
            'sentiment'      => $sentiment !== null ? (float) $sentiment : null,
            'phq9'           => $latestScreening?->phq9_score,
            'stress_index'   => $latestWearable?->stress_index !== null ? (float) $latestWearable->stress_index : null,
            'latest_emotion' => $latestEmotion,
        ];
    }

    /**
     * Compute weighted risk score (0–100) from gathered factors.
     *
     * @param  array<string, mixed>  $factors
     * @param  Collection<string, RiskThreshold>  $thresholds
     */
    public function computeWeightedScore(array $factors, Collection $thresholds): float
    {
        $factorMap = [
            'hrv'             => $factors['hrv'] ?? null,
            'sleep_duration'  => $factors['sleep_duration'] ?? null,
            'sentiment_score' => $factors['sentiment'] ?? null,
            'phq9_score'      => $factors['phq9'] ?? null,
            'stress_index'    => $factors['stress_index'] ?? null,
        ];

        $totalWeightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($factorMap as $paramName => $value) {
            if ($value === null || ! isset($thresholds[$paramName])) {
                continue;
            }

            $threshold = $thresholds[$paramName];
            $factorScore = $this->scoreForFactor((float) $value, $threshold);
            $weight = (float) $threshold->weight;

            $totalWeightedScore += $factorScore * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        // Normalize by available weights to account for missing factors
        return round($totalWeightedScore / $totalWeight, 2);
    }

    /**
     * Compute a 0–100 risk score for a single factor based on its threshold bands.
     * low band → 0–25 | medium band → 26–65 | high band → 66–100
     */
    public function scoreForFactor(float $value, RiskThreshold $threshold): float
    {
        $lowMin = (float) $threshold->low_min;
        $lowMax = (float) $threshold->low_max;
        $medMin = (float) $threshold->medium_min;
        $medMax = (float) $threshold->medium_max;
        $highMin = (float) $threshold->high_min;
        $highMax = (float) $threshold->high_max;

        if ($this->inRange($value, $lowMin, $lowMax)) {
            return $this->interpolate($value, $lowMin, $lowMax, 0.0, 25.0);
        }

        if ($this->inRange($value, $medMin, $medMax)) {
            return $this->interpolate($value, $medMin, $medMax, 26.0, 65.0);
        }

        if ($this->inRange($value, $highMin, $highMax)) {
            return $this->interpolate($value, $highMin, $highMax, 66.0, 100.0);
        }

        // Value outside defined ranges — clamp to nearest extreme
        $allValues = array_filter([$lowMin, $lowMax, $medMin, $medMax, $highMin, $highMax]);
        $isAboveMax = ! empty($allValues) && $value > max($allValues);

        return $isAboveMax ? 0.0 : 100.0;
    }

    public function scoreToLevel(float $score): string
    {
        return match (true) {
            $score >= 81 => 'critical',
            $score >= 61 => 'high',
            $score >= 31 => 'medium',
            default      => 'low',
        };
    }

    /**
     * @param  array<string, mixed>  $factors
     */
    private function generateSummary(array $factors, string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => 'Pasien dalam kondisi kritis. Tindakan segera diperlukan!',
            'high'     => 'Pasien berisiko tinggi. Intervensi dokter direkomendasikan segera.',
            'medium'   => 'Pasien menunjukkan beberapa indikator stres. Pemantauan rutin direkomendasikan.',
            default    => 'Kondisi mental pasien berada dalam kategori baik.',
        };
    }

    private function inRange(float $value, float $min, float $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Linear interpolation from [inMin, inMax] to [outMin, outMax].
     */
    private function interpolate(float $value, float $inMin, float $inMax, float $outMin, float $outMax): float
    {
        if ($inMin === $inMax) {
            return $outMin;
        }

        $ratio = ($value - $inMin) / ($inMax - $inMin);

        return round($outMin + ($outMax - $outMin) * $ratio, 2);
    }
}

