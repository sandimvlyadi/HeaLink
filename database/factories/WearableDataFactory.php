<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WearableData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WearableData>
 */
class WearableDataFactory extends Factory
{
    public function definition(): array
    {
        $hrv = fake()->randomFloat(2, 20, 90);
        $heartRate = (int) (75 - ($hrv * 0.3) + fake()->numberBetween(-5, 5));

        return [
            'user_id' => User::factory(),
            'hrv_score' => round($hrv, 2),
            'heart_rate' => max(50, min(110, $heartRate)),
            'stress_index' => round(100 - $hrv, 2),
            'device_type' => fake()->randomElement(['Garmin', 'Apple Watch', 'Simulated', 'Fitbit']),
            'is_simulated' => true,
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Mark as non-simulated (real device data).
     */
    public function realDevice(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_simulated' => false,
        ]);
    }
}
