<?php

namespace App\Services;

use App\Models\User;
use App\Models\WearableData;
use Illuminate\Database\Eloquent\Collection;

class WearableSimulatorService
{
    /**
     * Generate a realistic wearable data record for a user.
     * HRV follows a natural circadian rhythm (higher in morning, lower in afternoon).
     */
    public function generateForUser(User $user): WearableData
    {
        $hour = (int) now()->format('G');

        // Circadian rhythm: HRV peaks around 6 AM, dips around 18 PM
        $base = 50 + (10 * cos(($hour - 6) * M_PI / 12));

        // Add ±15ms natural noise
        $noise = (mt_rand(-150, 150) / 10);
        $hrv = max(15.0, min(100.0, $base + $noise));

        // Heart rate inversely correlated with HRV with small random variation
        $hr = (int) (75 - ($hrv * 0.3) + mt_rand(-5, 5));

        return WearableData::create([
            'user_id' => $user->id,
            'hrv_score' => round($hrv, 2),
            'heart_rate' => max(50, min(110, $hr)),
            'stress_index' => round(100 - $hrv, 2),
            'device_type' => 'Simulated',
            'is_simulated' => true,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Generate multiple wearable records for a user over a time range.
     * Useful for bulk seeding historical data.
     *
     * @return Collection<int, WearableData>
     */
    public function generateBulkForUser(User $user, int $count = 24): Collection
    {
        $records = new Collection;

        for ($i = 0; $i < $count; $i++) {
            $offsetHours = $count - $i;
            $recordedAt = now()->subHours($offsetHours);
            $hour = (int) $recordedAt->format('G');

            $base = 50 + (10 * cos(($hour - 6) * M_PI / 12));
            $noise = (mt_rand(-150, 150) / 10);
            $hrv = max(15.0, min(100.0, $base + $noise));
            $hr = (int) (75 - ($hrv * 0.3) + mt_rand(-5, 5));

            $records->push(WearableData::create([
                'user_id' => $user->id,
                'hrv_score' => round($hrv, 2),
                'heart_rate' => max(50, min(110, $hr)),
                'stress_index' => round(100 - $hrv, 2),
                'device_type' => 'Simulated',
                'is_simulated' => true,
                'recorded_at' => $recordedAt,
            ]));
        }

        return $records;
    }
}
