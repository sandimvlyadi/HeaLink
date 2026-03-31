<?php

use App\Models\SleepLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Laravel\Sanctum\Sanctum;

it('stores a sleep log', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/sleep', [
        'duration_minutes' => 480,
        'quality_score'    => 7.5,
        'quality_category' => 'good',
        'sleep_time'       => '22:30:00',
        'wake_time'        => '06:30:00',
        'sleep_date'       => now()->toDateString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.duration_minutes', 480)
        ->assertJsonPath('data.quality_category', 'good');

    $this->assertDatabaseHas('sleep_logs', [
        'user_id'          => $user->id,
        'duration_minutes' => 480,
    ]);
});

it('updates existing sleep log for same date', function () {
    $user = User::factory()->create();
    $date = now()->toDateString();
    SleepLog::factory()->for($user)->create(['sleep_date' => $date, 'quality_score' => 5.0]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/sleep', [
        'duration_minutes' => 360,
        'quality_score'    => 8.5,
        'sleep_date'       => $date,
    ])->assertOk()
        ->assertJsonPath('data.quality_score', '8.50');

    expect(SleepLog::where('user_id', $user->id)->count())->toBe(1);
});

it('validates required sleep fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/sleep', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['duration_minutes', 'quality_score', 'sleep_date']);
});

it('returns paginated sleep history', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    SleepLog::factory()
        ->for($user)
        ->count(5)
        ->state(new Sequence(
            ['sleep_date' => now()->subDays(1)->toDateString()],
            ['sleep_date' => now()->subDays(2)->toDateString()],
            ['sleep_date' => now()->subDays(3)->toDateString()],
            ['sleep_date' => now()->subDays(4)->toDateString()],
            ['sleep_date' => now()->subDays(5)->toDateString()],
        ))
        ->create();

    $this->getJson('/api/v1/sleep/history')
        ->assertOk()
        ->assertJsonPath('meta.total', 5);
});

it('requires auth for sleep endpoints', function () {
    $this->postJson('/api/v1/sleep', [])->assertUnauthorized();
    $this->getJson('/api/v1/sleep/history')->assertUnauthorized();
});
