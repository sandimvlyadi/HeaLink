<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'ongoing', 'completed', 'cancelled']);
        $scheduledAt = fake()->dateTimeBetween('-7 days', '+7 days');

        $startedAt = null;
        $endedAt = null;

        if (in_array($status, ['ongoing', 'completed'])) {
            $startedAt = $scheduledAt;
        }

        if ($status === 'completed') {
            $endedAt = (clone $startedAt)->modify('+'.fake()->numberBetween(15, 60).' minutes');
        }

        return [
            'patient_id' => User::factory()->patient(),
            'medic_id' => User::factory()->medic(),
            'session_token' => Str::random(128),
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => $status === 'completed' ? fake()->paragraph() : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'ended_at' => null,
            'notes' => null,
        ]);
    }

    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ongoing',
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
            'notes' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $scheduled = fake()->dateTimeBetween('-30 days', '-1 day');
            $started = $scheduled;
            $ended = (clone $started)->modify('+'.fake()->numberBetween(20, 60).' minutes');

            return [
                'status' => 'completed',
                'scheduled_at' => $scheduled,
                'started_at' => $started,
                'ended_at' => $ended,
                'notes' => fake()->paragraph(),
            ];
        });
    }
}
