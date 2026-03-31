<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed 1 admin, 3 medics, and 20 patients — each with a profile.
     */
    public function run(): void
    {
        // 1 Admin
        $admin = User::factory()->admin()->create([
            'name'  => 'Admin HeaLink',
            'email' => 'admin@healink.id',
        ]);
        UserProfile::factory()->create(['user_id' => $admin->id]);

        // 3 Medics (dokter)
        $medicData = [
            ['name' => 'Dr. Budi Santoso', 'email' => 'budi.santoso@healink.id'],
            ['name' => 'Dr. Siti Rahayu',  'email' => 'siti.rahayu@healink.id'],
            ['name' => 'Dr. Ahmad Fauzi',  'email' => 'ahmad.fauzi@healink.id'],
        ];

        foreach ($medicData as $data) {
            $medic = User::factory()->medic()->create($data);
            UserProfile::factory()->create(['user_id' => $medic->id]);
        }

        // 20 Patients (pasien)
        User::factory()->patient()->count(20)->create()->each(function (User $patient): void {
            UserProfile::factory()->create(['user_id' => $patient->id]);
        });
    }
}

