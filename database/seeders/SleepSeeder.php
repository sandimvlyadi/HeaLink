<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SleepSeeder extends Seeder
{
    /**
     * Seed 30 days of sleep logs per patient (1 record per day).
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->pluck('id');
        $today = Carbon::today();
        $chunk = [];
        $timestamp = now()->toDateTimeString();

        foreach ($patients as $userId) {
            for ($day = 29; $day >= 0; $day--) {
                $sleepDate = $today->copy()->subDays($day)->toDateString();
                $durationMins = mt_rand(240, 540); // 4–9 hours
                $qualityScore = round(mt_rand(300, 1000) / 100, 2); // 3.00–10.00
                $qualityCategory = match (true) {
                    $qualityScore >= 7.0 => 'good',
                    $qualityScore >= 5.0 => 'fair',
                    default => 'poor',
                };
                $sleepHour = mt_rand(21, 23);
                $sleepMinute = mt_rand(0, 59);
                $wakeHour = mt_rand(5, 8);
                $wakeMinute = mt_rand(0, 59);

                $chunk[] = [
                    'uuid' => Str::uuid(),
                    'user_id' => $userId,
                    'duration_minutes' => $durationMins,
                    'quality_score' => $qualityScore,
                    'quality_category' => $qualityCategory,
                    'sleep_time' => sprintf('%02d:%02d:00', $sleepHour, $sleepMinute),
                    'wake_time' => sprintf('%02d:%02d:00', $wakeHour, $wakeMinute),
                    'sleep_date' => $sleepDate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($chunk, 500) as $batch) {
            DB::table('sleep_logs')->insert($batch);
        }
    }
}
