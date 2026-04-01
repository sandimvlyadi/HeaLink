<?php

use App\Models\MentalStatusLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns paginated mental status logs for authenticated user', function () {
    $user = User::factory()->create();
    MentalStatusLog::factory()->for($user)->count(5)->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/mental-status');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'current_page', 'last_page', 'per_page', 'timestamp'],
        ])
        ->assertJsonPath('meta.total', 5);
});

it('does not return other users mental status logs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    MentalStatusLog::factory()->for($other)->count(3)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/mental-status')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('returns mental status logs ordered by most recent', function () {
    $user = User::factory()->create();
    MentalStatusLog::factory()->for($user)->create(['created_at' => now()->subDays(5), 'risk_score' => 20.00]);
    MentalStatusLog::factory()->for($user)->create(['created_at' => now(), 'risk_score' => 80.00]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/mental-status');

    $response->assertOk();
    expect($response->json('data.0.risk_score'))->toBe('80.00');
});

it('returns the latest mental status log', function () {
    $user = User::factory()->create();
    MentalStatusLog::factory()->for($user)->create(['created_at' => now()->subDay(), 'risk_score' => 30.00]);
    MentalStatusLog::factory()->for($user)->create(['created_at' => now(), 'risk_score' => 75.00]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/mental-status/latest');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.risk_score', '75.00');
});

it('returns null when no mental status log exists', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/mental-status/latest')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('requires auth for mental status endpoints', function () {
    $this->getJson('/api/v1/mental-status')->assertUnauthorized();
    $this->getJson('/api/v1/mental-status/latest')->assertUnauthorized();
});
