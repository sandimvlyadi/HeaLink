<?php

namespace Database\Factories;

use App\Models\ChatHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatHistory>
 */
class ChatHistoryFactory extends Factory
{
    /**
     * @var list<string>
     */
    private static array $userMessages = [
        'Saya merasa sangat lelah akhir-akhir ini.',
        'Sulit tidur beberapa hari terakhir.',
        'Saya tidak bisa berkonsentrasi.',
        'Perasaan saya campur aduk.',
        'Saya merasa lebih baik hari ini.',
        'Terima kasih atas sarannya.',
        'Saya mencoba menerapkan teknik relaksasi.',
        'Kadang saya merasa tidak berguna.',
        'Saya ingin berbicara dengan dokter.',
        'Bisakah kamu membantu saya?',
    ];

    /**
     * @var list<string>
     */
    private static array $aiMessages = [
        'Terima kasih sudah berbagi perasaanmu. Saya di sini untuk mendengarkan.',
        'Itu terdengar sangat sulit. Mari kita cari cara untuk membantumu.',
        'Apakah ada hal tertentu yang membuatmu merasa seperti itu?',
        'Saya memahami perasaanmu. Istirahat yang cukup sangat penting.',
        'Bagaimana tidurmu malam ini?',
        'Teknik pernapasan dalam bisa sangat membantu untuk relaksasi.',
        'Saya senang mendengar kamu merasa lebih baik hari ini!',
        'Mari kita bicarakan lebih lanjut tentang apa yang kamu rasakan.',
    ];

    public function definition(): array
    {
        $senderType = fake()->randomElement(['user', 'ai']);
        $message = $senderType === 'user'
            ? fake()->randomElement(self::$userMessages)
            : fake()->randomElement(self::$aiMessages);

        $sentimentScore = null;
        if ($senderType === 'user') {
            $sentimentScore = round(fake()->randomFloat(3, -1.0, 1.0), 3);
        }

        return [
            'user_id'          => User::factory(),
            'message'          => $message,
            'sender_type'      => $senderType,
            'sentiment_score'  => $sentimentScore,
            'detected_emotion' => fake()->optional(0.7)->randomElement([
                'calm', 'anxious', 'sad', 'angry', 'neutral', 'happy',
            ]),
            'context_data' => null,
            'is_flagged'   => fake()->boolean(5), // 5% chance flagged
        ];
    }

    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type'     => 'user',
            'sentiment_score' => round(fake()->randomFloat(3, -1.0, 1.0), 3),
        ]);
    }

    public function fromAi(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type'     => 'ai',
            'sentiment_score' => null,
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type'     => 'user',
            'sentiment_score' => round(fake()->randomFloat(3, -1.0, -0.3), 3),
            'detected_emotion' => fake()->randomElement(['sad', 'anxious', 'angry']),
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
        ]);
    }
}
