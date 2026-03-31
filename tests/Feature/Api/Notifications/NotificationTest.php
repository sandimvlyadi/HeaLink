<?php

use App\Models\Notification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns paginated notifications', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user)->count(3)->create(['is_read' => false]);
    Notification::factory()->for($user)->count(2)->create(['is_read' => true]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.unread_count', 3);
});

it('does not return other users notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Notification::factory()->for($other)->count(3)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('marks a notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->for($user)->create(['is_read' => false]);
    Sanctum::actingAs($user);

    $response = $this->putJson("/api/v1/notifications/{$notification->uuid}/read");

    $response->assertOk()
        ->assertJsonPath('data.is_read', true);

    $this->assertDatabaseHas('notifications', [
        'id'      => $notification->id,
        'is_read' => true,
    ]);
});

it('prevents marking another users notification as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = Notification::factory()->for($other)->create(['is_read' => false]);
    Sanctum::actingAs($user);

    $this->putJson("/api/v1/notifications/{$notification->uuid}/read")
        ->assertForbidden();
});

it('marks all notifications as read', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user)->count(3)->create(['is_read' => false]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(
        Notification::where('user_id', $user->id)->where('is_read', false)->count()
    )->toBe(0);
});

it('requires auth for notification endpoints', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
    $this->putJson('/api/v1/notifications/read-all')->assertUnauthorized();
});
