<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters:
     * 1. RiskThresholds — no FK dependencies
     * 2. Users + Profiles — foundation for all patient data
     * 3. Health Screenings — FK: users
     * 4. Wearable Data — FK: users (bulk insert, runs first before heavy seeders)
     * 5. Sleep Logs — FK: users
     * 6. Chat Histories — FK: users
     * 7. Mood Journals — FK: users
     * 8. Mental Status Logs — FK: users (recalculated from above data)
     * 9. Consultations + Facial Emotion Logs — FK: users
     */
    public function run(): void
    {
        $this->call([
            RiskThresholdSeeder::class,
            UserSeeder::class,
            HealthScreeningSeeder::class,
            WearableSeeder::class,
            SleepSeeder::class,
            ChatSeeder::class,
            MoodJournalSeeder::class,
            MentalStatusSeeder::class,
            ConsultationSeeder::class,
        ]);
    }
}

