<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\FacialEmotionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacialEmotionLog>
 */
class FacialEmotionLogFactory extends Factory
{
    /** @var list<string> */
    private static array $emotions = ['happy', 'sad', 'anxious', 'angry', 'neutral', 'calm'];

    public function definition(): array
    {
        $detectedMood = fake()->randomElement(self::$emotions);

        // Build a breakdown where detected_mood has the highest score
        $breakdown = [];
        $allocated = 0.0;

        foreach (self::$emotions as $emotion) {
            if ($emotion === $detectedMood) {
                continue;
            }
            $value = round(fake()->randomFloat(3, 0.01, 0.15), 3);
            $breakdown[$emotion] = $value;
            $allocated += $value;
        }

        $breakdown[$detectedMood] = round(max(0.001, 1.0 - $allocated), 3);

        return [
            'consultation_id'   => Consultation::factory(),
            'detected_mood'     => $detectedMood,
            'confidence'        => round(fake()->randomFloat(3, 0.50, 1.000), 3),
            'emotion_breakdown' => $breakdown,
            'captured_at'       => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
