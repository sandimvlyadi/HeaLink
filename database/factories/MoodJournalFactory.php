<?php

namespace Database\Factories;

use App\Models\MoodJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodJournal>
 */
class MoodJournalFactory extends Factory
{
    /**
     * @var array<string, string>
     */
    private static array $moodEmoji = [
        'very_bad' => '😢',
        'bad' => '😔',
        'neutral' => '😐',
        'good' => '😊',
        'very_good' => '😄',
    ];

    public function definition(): array
    {
        $mood = fake()->randomElement(['very_bad', 'bad', 'neutral', 'good', 'very_good']);

        return [
            'user_id' => User::factory(),
            'emoji' => self::$moodEmoji[$mood],
            'mood' => $mood,
            'note' => fake()->optional(0.7)->sentence(),
            'journal_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }
}
