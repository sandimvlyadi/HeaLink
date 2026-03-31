<?php

use App\Jobs\AnalyzeChatSentimentJob;
use App\Models\ChatHistory;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

it('stores a chat message and dispatches sentiment analysis job', function () {
    Bus::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/chat', [
        'message' => 'Saya merasa sedih hari ini',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'user_message' => ['uuid', 'message', 'sender_type'],
            ],
        ])
        ->assertJsonPath('data.user_message.sender_type', 'user');

    expect(ChatHistory::where('user_id', $user->id)->where('sender_type', 'user')->count())->toBe(1);

    Bus::assertDispatched(AnalyzeChatSentimentJob::class);
});

it('validates message is required', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/chat', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});

it('returns paginated chat history', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    ChatHistory::factory()->for($user)->count(3)->create(['sender_type' => 'user']);

    $response = $this->getJson('/api/v1/chat/history');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total']])
        ->assertJsonPath('meta.total', 3);
});

it('does not expose other users chat history', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    ChatHistory::factory()->for($other)->count(3)->create();

    $this->getJson('/api/v1/chat/history')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('requires auth for chat endpoints', function () {
    $this->postJson('/api/v1/chat', [])->assertUnauthorized();
    $this->getJson('/api/v1/chat/history')->assertUnauthorized();
});
