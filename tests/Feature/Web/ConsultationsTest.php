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
            ->has('consultation.uuid')
            ->missing('consultation.id')
        );
});

test('assigned medic can start a pending consultation', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($medic)
        ->patch(route('consultations.start', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.room', $consultation->uuid));

    expect($consultation->refresh()->status)->toBe('ongoing');
});

test('admin can start a pending consultation', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create([
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('consultations.start', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.room', $consultation->uuid));

    expect($consultation->refresh()->status)->toBe('ongoing');
});

test('unrelated medic cannot start a consultation', function () {
    $medic = User::factory()->medic()->create();
    $otherMedic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($otherMedic)
        ->patch(route('consultations.start', ['consultation' => $consultation->uuid]))
        ->assertForbidden();
});

test('assigned medic can complete an ongoing consultation', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->ongoing()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($medic)
        ->patch(route('consultations.complete', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.index'));

    expect($consultation->refresh()->status)->toBe('completed');
});

test('admin can complete an ongoing consultation', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->ongoing()->create([
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('consultations.complete', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.index'));

    expect($consultation->refresh()->status)->toBe('completed');
});

test('assigned medic can cancel a pending consultation', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create([
        'medic_id'   => $medic->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($medic)
        ->patch(route('consultations.cancel', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.index'));

    expect($consultation->refresh()->status)->toBe('cancelled');
});

test('admin can cancel an ongoing consultation', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->ongoing()->create([
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('consultations.cancel', ['consultation' => $consultation->uuid]))
        ->assertRedirect(route('consultations.index'));

    expect($consultation->refresh()->status)->toBe('cancelled');
});
