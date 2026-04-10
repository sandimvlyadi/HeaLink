<?php

namespace Database\Factories;

use App\Models\HealthScreening;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthScreening>
 */
class HealthScreeningFactory extends Factory
{
    public function definition(): array
    {
        $height = fake()->randomFloat(2, 150, 190);
        $weight = fake()->randomFloat(2, 45, 110);
        $heightM = $height / 100;
        $bmi = round($weight / ($heightM ** 2), 2);

        $phq9Answers = array_map(fn () => fake()->numberBetween(0, 3), range(1, 9));
        $phq9Score = array_sum($phq9Answers);

        return [
            'user_id' => User::factory(),
            'height_cm' => $height,
            'weight_kg' => $weight,
            'bmi' => $bmi,
            'systolic' => fake()->numberBetween(100, 160),
            'diastolic' => fake()->numberBetween(60, 100),
            'phq9_answers' => $phq9Answers,
            'phq9_score' => $phq9Score,
        ];
    }
}
