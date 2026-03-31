<?php

use App\Models\HealthScreening;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a health screening with BMI and PHQ-9 auto-calculated', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/screening', [
        'height_cm'    => 170.0,
        'weight_kg'    => 70.0,
        'systolic'     => 120,
        'diastolic'    => 80,
        'phq9_answers' => [1, 0, 2, 1, 0, 1, 0, 1, 2],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.phq9_score', 8)
        ->assertJsonPath('data.bmi', '24.22');

    $this->assertDatabaseHas('health_screenings', [
        'user_id'    => $user->id,
        'phq9_score' => 8,
    ]);
});

it('updates existing health screening', function () {
    $user = User::factory()->create();
    HealthScreening::factory()->for($user)->create(['height_cm' => 165, 'weight_kg' => 60]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/screening', [
        'height_cm' => 170.0,
        'weight_kg' => 72.0,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.weight_kg', '72.00');

    expect(HealthScreening::where('user_id', $user->id)->count())->toBe(1);
});

it('calculates BMI correctly', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // BMI = 90 / (1.80^2) = 27.78
    $this->putJson('/api/v1/screening', [
        'height_cm' => 180.0,
        'weight_kg' => 90.0,
    ])->assertCreated()
        ->assertJsonPath('data.bmi', '27.78');
});

it('returns latest screening', function () {
    $user = User::factory()->create();
    HealthScreening::factory()->for($user)->create(['phq9_score' => 10]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/screening/latest')
        ->assertOk()
        ->assertJsonPath('data.phq9_score', 10);
});

it('returns null when no screening exists', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/screening/latest')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('validates phq9_answers must be 9 items', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/screening', ['phq9_answers' => [1, 2, 3]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phq9_answers']);
});
