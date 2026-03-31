<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WearableSeeder extends Seeder
{
    /**
     * Seed 30 days × 24 hourly wearable records per patient (simulated).
     * Uses bulk insert for performance (~14400 total records).
     */
    public function run(): void
    {
        $patients  = User::where('role', 'patient')->pluck('id');
        $now       = Carbon::now()->setMinute(0)->setSecond(0)->setMicrosecond(0);
        $chunk     = [];
        $chunkSize = 500;
        $timestamp = now()->toDateTimeString();

        foreach ($patients as $userId) {
            for ($day = 29; $day >= 0; $day--) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $recordedAt = $now->copy()->subDays($day)->setHour($hour);

                    // Circadian rhythm: HRV higher in morning, lower in evening
                    $base  = 50 + (10 * cos(($hour - 6) * M_PI / 12));
                    $noise = mt_rand(-150, 150) / 10; // ±15 ms noise
                    $hrv   = round(max(15.0, min(100.0, $base + $noise)), 2);
                    $hr    = max(50, min(110, (int) (75 - ($hrv * 0.3) + mt_rand(-5, 5))));

                    $chunk[] = [
                        'uuid'         => Str::uuid(),
                        'user_id'      => $userId,
                        'hrv_score'    => $hrv,
                        'heart_rate'   => $hr,
                        'stress_index' => round(100 - $hrv, 2),
                        'device_type'  => 'Simulated',
                        'is_simulated' => true,
                        'recorded_at'  => $recordedAt->toDateTimeString(),
                        'created_at'   => $timestamp,
                        'updated_at'   => $timestamp,
                    ];

                    if (count($chunk) >= $chunkSize) {
                        DB::table('wearable_data')->insert($chunk);
                        $chunk = [];
                    }
                }
            }
        }

        if (! empty($chunk)) {
            DB::table('wearable_data')->insert($chunk);
        }
    }
}

