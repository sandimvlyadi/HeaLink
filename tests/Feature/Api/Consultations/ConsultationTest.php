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
