<?php

namespace Database\Factories;

use App\Models\MentalStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentalStatusLog>
 */
class MentalStatusLogFactory extends Factory
{
    public function definition(): array
    {
        $riskScore = fake()->randomFloat(2, 0, 100);
        $riskLevel = match (true) {
            $riskScore >= 81 => 'critical',
            $riskScore >= 61 => 'high',
            $riskScore >= 31 => 'medium',
            default          => 'low',
        };

        return [
            'user_id'    => User::factory(),
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'detected_emotion' => fake()->optional(0.8)->randomElement([
                'calm', 'anxious', 'sad', 'angry', 'neutral',
            ]),
            'summary_note' => fake()->optional(0.6)->sentence(),
            'contributing_factors' => [
                'hrv'       => fake()->randomFloat(2, 0, 1),
                'sleep'     => fake()->randomFloat(2, 0, 1),
                'sentiment' => fake()->randomFloat(3, -1, 0),
                'phq9'      => fake()->randomFloat(2, 0, 1),
            ],
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'critical',
            'risk_score' => fake()->randomFloat(2, 81, 100),
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'low',
            'risk_score' => fake()->randomFloat(2, 0, 30),
        ]);
    }
}
