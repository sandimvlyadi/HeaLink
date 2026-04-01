<?php

use App\Models\Consultation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns paginated consultations for authenticated patient', function () {
    $patient = User::factory()->patient()->create();
    Consultation::factory()->count(3)->create(['patient_id' => $patient->id]);
    Sanctum::actingAs($patient);

    $response = $this->getJson('/api/v1/consultations');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'current_page', 'last_page', 'per_page', 'timestamp'],
        ])
        ->assertJsonPath('meta.total', 3);
});

it('does not return other patients consultations', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    Consultation::factory()->count(3)->create(['patient_id' => $other->id]);
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/consultations')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('filters consultations by status', function () {
    $patient = User::factory()->patient()->create();
    Consultation::factory()->pending()->count(2)->create(['patient_id' => $patient->id]);
    Consultation::factory()->completed()->count(3)->create(['patient_id' => $patient->id]);
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/consultations?status=pending')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    $this->getJson('/api/v1/consultations?status=completed')
        ->assertOk()
        ->assertJsonPath('meta.total', 3);
});

it('returns consultation detail for the owner', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->create(['patient_id' => $patient->id]);
    Sanctum::actingAs($patient);

    $response = $this->getJson("/api/v1/consultations/{$consultation->uuid}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['uuid', 'status']]);
});

it('forbids accessing another patients consultation', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    $consultation = Consultation::factory()->create(['patient_id' => $other->id]);
    Sanctum::actingAs($patient);

    $this->getJson("/api/v1/consultations/{$consultation->uuid}")
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('returns 404 for non-existent consultation', function () {
    $patient = User::factory()->patient()->create();
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/consultations/non-existent-uuid')
        ->assertNotFound();
});

it('requires auth for consultation endpoints', function () {
    $this->getJson('/api/v1/consultations')->assertUnauthorized();
    $this->getJson('/api/v1/consultations/some-uuid')->assertUnauthorized();
});

// ── store ────────────────────────────────────────────────────────────────────

it('patient can book a consultation', function () {
    $patient = User::factory()->patient()->create();
    $medic = User::factory()->medic()->create();
    Sanctum::actingAs($patient);

    $response = $this->postJson('/api/v1/consultations', [
        'medic_id' => $medic->uuid,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');
});

it('validates medic_id must be a valid medic uuid', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/consultations', [
        'medic_id' => $other->uuid,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
    ])->assertUnprocessable();
});

it('validates scheduled_at must be in the future', function () {
    $patient = User::factory()->patient()->create();
    $medic = User::factory()->medic()->create();
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/consultations', [
        'medic_id' => $medic->uuid,
        'scheduled_at' => now()->subHour()->toDateTimeString(),
    ])->assertUnprocessable();
});

// ── cancel ───────────────────────────────────────────────────────────────────

it('patient can cancel own pending consultation', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create(['patient_id' => $patient->id]);
    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/cancel")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'cancelled');
});

it('patient cannot cancel a non-pending consultation', function () {
    $patient = User::factory()->patient()->create();
    $consultation = Consultation::factory()->completed()->create(['patient_id' => $patient->id]);
    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/cancel")
        ->assertUnprocessable();
});

it('patient cannot cancel another patients consultation', function () {
    $patient = User::factory()->patient()->create();
    $other = User::factory()->patient()->create();
    $consultation = Consultation::factory()->pending()->create(['patient_id' => $other->id]);
    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/cancel")
        ->assertForbidden();
});

it('medic can cancel own pending consultation', function () {
    $medic = User::factory()->medic()->create();
    $consultation = Consultation::factory()->pending()->create(['medic_id' => $medic->id]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

// ── start ─────────────────────────────────────────────────────────────────────

it('medic can start own pending consultation', function () {
    $medic = User::factory()->medic()->create();
    $consultation = Consultation::factory()->pending()->create(['medic_id' => $medic->id]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'ongoing');
});

it('medic cannot start another medics consultation', function () {
    $medic = User::factory()->medic()->create();
    $other = User::factory()->medic()->create();
    $consultation = Consultation::factory()->pending()->create(['medic_id' => $other->id]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/start")
        ->assertForbidden();
});

it('medic cannot start a non-pending consultation', function () {
    $medic = User::factory()->medic()->create();
    $consultation = Consultation::factory()->completed()->create(['medic_id' => $medic->id]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/start")
        ->assertUnprocessable();
});

// ── complete ──────────────────────────────────────────────────────────────────

it('medic can complete own ongoing consultation', function () {
    $medic = User::factory()->medic()->create();
    $consultation = Consultation::factory()->create([
        'medic_id' => $medic->id,
        'status' => 'ongoing',
        'started_at' => now()->subMinutes(10),
    ]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('medic cannot complete a non-ongoing consultation', function () {
    $medic = User::factory()->medic()->create();
    $consultation = Consultation::factory()->pending()->create(['medic_id' => $medic->id]);
    Sanctum::actingAs($medic);

    $this->patchJson("/api/v1/consultations/{$consultation->uuid}/complete")
        ->assertUnprocessable();
});
