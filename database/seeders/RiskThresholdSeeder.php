<?php

namespace Database\Seeders;

use App\Models\RiskThreshold;
use Illuminate\Database\Seeder;

class RiskThresholdSeeder extends Seeder
{
    /**
     * Seed 5 default risk threshold parameters.
     * Weights must total exactly 1.0.
     */
    public function run(): void
    {
        $thresholds = [
            [
                'parameter_name' => 'hrv',
                'low_min'        => 60.000,
                'low_max'        => 100.000,
                'medium_min'     => 40.000,
                'medium_max'     => 59.999,
                'high_min'       => 15.000,
                'high_max'       => 39.999,
                'weight'         => 0.300,
                'description'    => 'Heart Rate Variability — semakin tinggi semakin baik (ms). Normal ≥60ms.',
                'is_active'      => true,
            ],
            [
                'parameter_name' => 'sleep_duration',
                'low_min'        => 420.000,
                'low_max'        => 540.000,
                'medium_min'     => 300.000,
                'medium_max'     => 419.999,
                'high_min'       => 1.000,
                'high_max'       => 299.999,
                'weight'         => 0.200,
                'description'    => 'Durasi tidur (menit). Normal 7–9 jam = 420–540 menit.',
                'is_active'      => true,
            ],
            [
                'parameter_name' => 'sentiment_score',
                'low_min'        => 0.000,
                'low_max'        => 1.000,
                'medium_min'     => -0.500,
                'medium_max'     => -0.001,
                'high_min'       => -1.000,
                'high_max'       => -0.501,
                'weight'         => 0.250,
                'description'    => 'Rata-rata skor sentimen chat. Rentang: -1.0 (sangat negatif) hingga +1.0 (sangat positif).',
                'is_active'      => true,
            ],
            [
                'parameter_name' => 'phq9_score',
                'low_min'        => 0.000,
                'low_max'        => 9.000,
                'medium_min'     => 10.000,
                'medium_max'     => 19.000,
                'high_min'       => 20.000,
                'high_max'       => 27.000,
                'weight'         => 0.150,
                'description'    => 'Skor PHQ-9 skrining depresi (0=minimal, 9=ringan, 19=sedang, 27=sangat berat).',
                'is_active'      => true,
            ],
            [
                'parameter_name' => 'stress_index',
                'low_min'        => 0.000,
                'low_max'        => 30.000,
                'medium_min'     => 30.001,
                'medium_max'     => 60.000,
                'high_min'       => 60.001,
                'high_max'       => 100.000,
                'weight'         => 0.100,
                'description'    => 'Indeks stres dari data wearable (0=sangat santai, 100=sangat stres).',
                'is_active'      => true,
            ],
        ];

        foreach ($thresholds as $threshold) {
            RiskThreshold::firstOrCreate(
                ['parameter_name' => $threshold['parameter_name']],
                $threshold
            );
        }
    }
}

