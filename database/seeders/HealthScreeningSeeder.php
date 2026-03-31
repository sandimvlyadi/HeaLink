<?php

namespace Database\Seeders;

use App\Models\HealthScreening;
use App\Models\User;
use Illuminate\Database\Seeder;

class HealthScreeningSeeder extends Seeder
{
    /**
     * Seed 1 health screening per patient.
     */
    public function run(): void
    {
        User::where('role', 'patient')->get()->each(function (User $patient): void {
            HealthScreening::factory()->create(['user_id' => $patient->id]);
        });
    }
}

