<?php

use App\Models\User;
use App\Models\WearableData;
use App\Services\WearableSimulatorService;

it('generates wearable data record for a user', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $record = $service->generateForUser($user);

    expect($record)->toBeInstanceOf(WearableData::class)
        ->and($record->user_id)->toBe($user->id)
        ->and($record->is_simulated)->toBeTrue()
        ->and($record->device_type)->toBe('Simulated');
});

it('generates hrv score within realistic range (15–100)', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $record = $service->generateForUser($user);

    expect((float) $record->hrv_score)->toBeGreaterThanOrEqual(15.0)
        ->toBeLessThanOrEqual(100.0);
});

it('generates heart rate within clamped range (50–110)', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $record = $service->generateForUser($user);

    expect($record->heart_rate)->toBeGreaterThanOrEqual(50)
        ->toBeLessThanOrEqual(110);
});

it('generates stress index as inverse of hrv', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $record = $service->generateForUser($user);

    // stress_index = 100 - hrv_score (rounded to 2 decimal places)
    $expected = round(100 - (float) $record->hrv_score, 2);

    expect((float) $record->stress_index)->toBe($expected);
});

it('persists wearable data to the database', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $service->generateForUser($user);

    $this->assertDatabaseHas('wearable_data', [
        'user_id'      => $user->id,
        'is_simulated' => true,
        'device_type'  => 'Simulated',
    ]);
});

it('generates bulk wearable records for the requested count', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $records = $service->generateBulkForUser($user, 5);

    expect($records)->toHaveCount(5);

    $this->assertDatabaseCount('wearable_data', 5);
});

it('bulk records are in chronological order', function () {
    $user = User::factory()->create();
    $service = new WearableSimulatorService;

    $records = $service->generateBulkForUser($user, 3);

    $timestamps = $records->pluck('recorded_at')->map(fn ($dt) => $dt->timestamp);

    expect($timestamps[0])->toBeLessThan($timestamps[1])
        ->and($timestamps[1])->toBeLessThan($timestamps[2]);
});

