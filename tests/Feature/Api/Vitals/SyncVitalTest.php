<?php

use App\Models\User;
use App\Models\WearableData;
use Laravel\Sanctum\Sanctum;

it('syncs vital data', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/vitals/sync', [
        'hrv_score' => 65.50,
        'heart_rate' => 72,
        'device_type' => 'Garmin',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.hrv_score', '65.50')
        ->assertJsonMissing(['id']);

    $this->assertDatabaseHas('wearable_data', [
        'user_id' => $user->id,
        'heart_rate' => 72,
    ]);
});

it('auto-calculates stress_index from hrv when not provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/vitals/sync', [
        'hrv_score' => 60.00,
        'heart_rate' => 70,
    ])->assertCreated()
        ->assertJsonPath('data.stress_index', '40.00');
});

it('validates required vital fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/vitals/sync', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hrv_score', 'heart_rate']);
});

it('returns latest vital', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    WearableData::factory()->for($user)->create(['hrv_score' => 55.10, 'recorded_at' => now()->subHour()]);
    WearableData::factory()->for($user)->create(['hrv_score' => 70.20, 'recorded_at' => now()]);

    $response = $this->getJson('/api/v1/vitals/latest');

    $response->assertOk()
        ->assertJsonPath('data.hrv_score', '70.20');
});

it('returns null when no vitals exist', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/vitals/latest')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('returns paginated vital history', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    WearableData::factory()->for($user)->count(5)->create();

    $response = $this->getJson('/api/v1/vitals/history');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total', 'current_page']]);

    expect($response->json('meta.total'))->toBe(5);
});

it('requires auth for vitals endpoints', function () {
    $this->postJson('/api/v1/vitals/sync', [])->assertUnauthorized();
    $this->getJson('/api/v1/vitals/latest')->assertUnauthorized();
    $this->getJson('/api/v1/vitals/history')->assertUnauthorized();
});
