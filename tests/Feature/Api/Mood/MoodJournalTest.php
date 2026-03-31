<?php

use App\Models\MoodJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Laravel\Sanctum\Sanctum;

it('stores a mood journal entry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/mood', [
        'emoji'        => '😊',
        'mood'         => 'good',
        'note'         => 'Hari yang menyenangkan',
        'journal_date' => now()->toDateString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.mood', 'good');

    $this->assertDatabaseHas('mood_journals', [
        'user_id' => $user->id,
        'mood'    => 'good',
    ]);
});

it('updates existing mood journal for same date', function () {
    $user = User::factory()->create();
    $date = now()->toDateString();
    MoodJournal::factory()->for($user)->create(['journal_date' => $date, 'mood' => 'bad']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/mood', [
        'mood'         => 'very_good',
        'journal_date' => $date,
    ])->assertOk()
        ->assertJsonPath('data.mood', 'very_good');

    expect(MoodJournal::where('user_id', $user->id)->count())->toBe(1);
});

it('validates mood is in allowed values', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/mood', [
        'mood'         => 'excellent',
        'journal_date' => now()->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['mood']);
});

it('returns paginated mood journal list', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    MoodJournal::factory()
        ->for($user)
        ->count(4)
        ->state(new Sequence(
            ['journal_date' => now()->subDays(1)->toDateString()],
            ['journal_date' => now()->subDays(2)->toDateString()],
            ['journal_date' => now()->subDays(3)->toDateString()],
            ['journal_date' => now()->subDays(4)->toDateString()],
        ))
        ->create();

    $this->getJson('/api/v1/mood')
        ->assertOk()
        ->assertJsonPath('meta.total', 4);
});

it('filters mood journal by date', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    MoodJournal::factory()->for($user)->create(['journal_date' => $today, 'mood' => 'good']);
    MoodJournal::factory()->for($user)->create(['journal_date' => $yesterday, 'mood' => 'bad']);

    $this->getJson("/api/v1/mood?date={$today}")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.mood', 'good');
});

it('requires auth for mood endpoints', function () {
    $this->postJson('/api/v1/mood', [])->assertUnauthorized();
    $this->getJson('/api/v1/mood')->assertUnauthorized();
});
