<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MoodJournalSeeder extends Seeder
{
    /** @var array<string, string> */
    private array $moodEmoji = [
        'very_bad' => '😢',
        'bad' => '😔',
        'neutral' => '😐',
        'good' => '😊',
        'very_good' => '😄',
    ];

    /** @var list<string> */
    private array $moods = ['very_bad', 'bad', 'neutral', 'good', 'very_good'];

    /**
     * Seed 30 days of mood journals per patient (1 entry per day).
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->pluck('id');
        $today = Carbon::today();
        $chunk = [];
        $timestamp = now()->toDateTimeString();

        foreach ($patients as $userId) {
            for ($day = 29; $day >= 0; $day--) {
                $journalDate = $today->copy()->subDays($day)->toDateString();
                $mood = $this->moods[array_rand($this->moods)];

                $chunk[] = [
                    'uuid' => Str::uuid(),
                    'user_id' => $userId,
                    'emoji' => $this->moodEmoji[$mood],
                    'mood' => $mood,
                    'note' => mt_rand(0, 10) > 3 ? null : 'Catatan singkat untuk hari ini.',
                    'journal_date' => $journalDate,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        foreach (array_chunk($chunk, 500) as $batch) {
            DB::table('mood_journals')->insert($batch);
        }
    }
}
