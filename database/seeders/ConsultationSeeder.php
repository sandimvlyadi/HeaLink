<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\FacialEmotionLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConsultationSeeder extends Seeder
{
    /**
     * Seed 3–5 consultations per patient (mixed statuses).
     * Completed consultations get a few facial emotion log entries.
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();
        $medics = User::where('role', 'medic')->pluck('id');

        foreach ($patients as $patient) {
            $count = mt_rand(3, 5);

            for ($i = 0; $i < $count; $i++) {
                $medicId = $medics->random();
                $consultation = Consultation::factory()->create([
                    'patient_id' => $patient->id,
                    'medic_id' => $medicId,
                ]);

                // Seed a few facial emotion logs for completed consultations
                if ($consultation->status === 'completed') {
                    $logCount = mt_rand(3, 8);
                    FacialEmotionLog::factory()->count($logCount)->create([
                        'consultation_id' => $consultation->id,
                        'captured_at' => fake()->dateTimeBetween(
                            $consultation->started_at ?? now()->subHour(),
                            $consultation->ended_at ?? now()
                        ),
                    ]);
                }
            }
        }
    }
}
