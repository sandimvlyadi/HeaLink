<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MentalStatusSeeder extends Seeder
{
    /**
     * Seed mental status logs per patient, derived from seeded wearable/sleep/chat data.
     * Creates 1 log per day for the last 30 days.
     */
    public function run(): void
    {
        $patients  = User::where('role', 'patient')->pluck('id');
        $today     = Carbon::today();
        $chunk     = [];
        $timestamp = now()->toDateTimeString();
        $emotions  = ['calm', 'anxious', 'sad', 'angry', 'neutral'];
        $levels    = ['low', 'medium', 'high', 'critical'];

        foreach ($patients as $userId) {
            // Gather average HRV from wearable_data for this patient
            $avgHrv       = DB::table('wearable_data')
                ->where('user_id', $userId)
                ->avg('hrv_score') ?? 55.0;

            // Gather average sleep quality
            $avgSleep     = DB::table('sleep_logs')
                ->where('user_id', $userId)
                ->avg('quality_score') ?? 5.0;

            // Gather average sentiment from chat
            $avgSentiment = DB::table('chat_histories')
                ->where('user_id', $userId)
                ->whereNotNull('sentiment_score')
                ->avg('sentiment_score') ?? 0.0;

            // Gather latest PHQ-9 score
            $phq9Score    = DB::table('health_screenings')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->value('phq9_score') ?? 5;

            // Simple weighted risk score calculation
            $hrvFactor       = max(0, min(1, 1 - ($avgHrv / 100)));
            $sleepFactor     = max(0, min(1, 1 - ($avgSleep / 10)));
            $sentimentFactor = max(0, min(1, ($avgSentiment * -0.5) + 0.5));
            $phq9Factor      = max(0, min(1, $phq9Score / 27));

            $baseScore = ($hrvFactor * 30) + ($sleepFactor * 20) + ($sentimentFactor * 25) + ($phq9Factor * 15);

            for ($day = 29; $day >= 0; $day--) {
                // Add day-to-day variance
                $variance   = mt_rand(-1000, 1000) / 100; // ±10 points
                $riskScore  = round(max(0.0, min(100.0, $baseScore + $variance + 10 * sin($day * 0.3))), 2);
                $riskLevel  = match (true) {
                    $riskScore >= 81 => 'critical',
                    $riskScore >= 61 => 'high',
                    $riskScore >= 31 => 'medium',
                    default          => 'low',
                };

                $dayDate = $today->copy()->subDays($day);

                $chunk[] = [
                    'uuid'               => Str::uuid(),
                    'user_id'             => $userId,
                    'risk_level'          => $riskLevel,
                    'risk_score'          => $riskScore,
                    'detected_emotion'    => $emotions[array_rand($emotions)],
                    'summary_note'        => "Evaluasi otomatis status mental hari ke-{$day}.",
                    'contributing_factors' => json_encode([
                        'hrv'       => round($hrvFactor, 3),
                        'sleep'     => round($sleepFactor, 3),
                        'sentiment' => round($sentimentFactor, 3),
                        'phq9'      => round($phq9Factor, 3),
                    ]),
                    'created_at' => $dayDate->toDateTimeString(),
                    'updated_at' => $dayDate->toDateTimeString(),
                ];
            }
        }

        foreach (array_chunk($chunk, 500) as $batch) {
            DB::table('mental_status_logs')->insert($batch);
        }
    }
}

