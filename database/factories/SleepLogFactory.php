<?php

namespace Database\Factories;

use App\Models\SleepLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SleepLog>
 */
class SleepLogFactory extends Factory
{
    public function definition(): array
    {
        $durationMinutes = fake()->numberBetween(240, 540); // 4–9 jam
        $qualityScore = round(fake()->randomFloat(2, 3.0, 10.0), 2);
        $qualityCategory = match (true) {
            $qualityScore >= 7.0 => 'good',
            $qualityScore >= 5.0 => 'fair',
            default => 'poor',
        };

        $sleepHour = fake()->numberBetween(21, 24); // 21:00 - 00:00
        $sleepMinute = fake()->numberBetween(0, 59);
        $wakeHour = fake()->numberBetween(5, 8);
        $wakeMinute = fake()->numberBetween(0, 59);

        return [
            'user_id' => User::factory(),
            'duration_minutes' => $durationMinutes,
            'quality_score' => $qualityScore,
            'quality_category' => $qualityCategory,
            'sleep_time' => sprintf('%02d:%02d:00', $sleepHour % 24, $sleepMinute),
            'wake_time' => sprintf('%02d:%02d:00', $wakeHour, $wakeMinute),
            'sleep_date' => fake()->unique()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }

    public function poor(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality_score' => fake()->randomFloat(2, 1.0, 4.9),
            'quality_category' => 'poor',
            'duration_minutes' => fake()->numberBetween(120, 300),
        ]);
    }
}
