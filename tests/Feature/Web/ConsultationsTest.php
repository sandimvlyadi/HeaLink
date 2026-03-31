<?php

use App\Models\Consultation;
use App\Models\User;

test('guests are redirected from consultations index', function () {
    $this->get(route('consultations.index'))->assertRedirect(route('login'));
});

test('medic can view consultations index', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('consultations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('consultations/index')
            ->has('consultations.data')
            ->has('patients')
        );
});

test('medic can view consultation room', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($medic)
        ->get(route('consultations.room', ['consultation' => $consultation->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('consultations/room')
            ->has('consultation')
        );
});

test('consultation room response exposes uuid not id', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($medic)
        ->get(route('consultations.room', ['consultation' => $consultation->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('consultations/room')
            ->has('consultation.data.uuid')
            ->missing('consultation.data.id')
        );
});
