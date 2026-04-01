<?php

use App\Models\Consultation;
use App\Models\User;

// ── index ─────────────────────────────────────────────────────────────────────

test('guests are redirected from patient consultations index', function () {
    $this->get(route('patient.consultations.index'))->assertRedirect(route('login'));
});

test('patient can view their consultations list', function () {
    $patient = User::factory()->patient()->create();
    Consultation::factory()->count(2)->create(['patient_id' => $patient->id]);

    $this->actingAs($patient)
        ->get(route('patient.consultations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patient/consultations/index')
            ->has('consultations.data', 2)
        );
});

test('patient only sees own consultations on the index page', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    Consultation::factory()->count(3)->create(['patient_id' => $other->id]);

    $this->actingAs($patient)
        ->get(route('patient.consultations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('consultations.data', 0));
});

test('medic cannot access patient consultations index', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('patient.consultations.index'))
        ->assertForbidden();
});

// ── create ────────────────────────────────────────────────────────────────────

test('patient can view the book consultation form', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->get(route('patient.consultations.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patient/consultations/create')
            ->has('medics')
        );
});

// ── store ─────────────────────────────────────────────────────────────────────

test('patient can book a consultation', function () {
    $patient = User::factory()->patient()->create();
    $medic = User::factory()->medic()->create();

    $this->actingAs($patient)
        ->post(route('patient.consultations.store'), [
            'medic_id' => $medic->uuid,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect(route('patient.consultations.index'))
        ->assertSessionHas('status', 'booked');

    $this->assertDatabaseHas('consultations', [
        'patient_id' => $patient->id,
        'medic_id' => $medic->id,
        'status' => 'pending',
    ]);
});

test('patient cannot book with an invalid medic uuid', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->post(route('patient.consultations.store'), [
            'medic_id' => 'not-a-medic',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('medic_id');
});

// ── show ──────────────────────────────────────────────────────────────────────

test('patient can view own consultation detail', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create(['patient_id' => $patient->id]);

    $this->actingAs($patient)
        ->get(route('patient.consultations.show', ['consultation' => $consultation->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patient/consultations/show')
            ->has('consultation')
        );
});

test('patient cannot view another patients consultation', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create(['patient_id' => $other->id]);

    $this->actingAs($patient)
        ->get(route('patient.consultations.show', ['consultation' => $consultation->uuid]))
        ->assertForbidden();
});

// ── cancel ────────────────────────────────────────────────────────────────────

test('patient can cancel own pending consultation', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create(['patient_id' => $patient->id]);

    $this->actingAs($patient)
        ->patch(route('patient.consultations.cancel', ['consultation' => $consultation->uuid]))
        ->assertRedirect();

    expect($consultation->refresh()->status)->toBe('cancelled');
});

test('patient cannot cancel a non-pending consultation', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->completed()->create(['patient_id' => $patient->id]);

    $this->actingAs($patient)
        ->patch(route('patient.consultations.cancel', ['consultation' => $consultation->uuid]))
        ->assertRedirect()
        ->assertSessionHasErrors('status');
});
