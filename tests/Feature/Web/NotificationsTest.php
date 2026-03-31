<?php

use App\Models\Notification;
use App\Models\User;

test('guests are redirected from notifications index', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

test('medic can view notifications index', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications.data')
            ->has('unread_count')
        );
});

test('medic can mark a notification as read', function () {
    $medic = User::factory()->medic()->create();
    $notification = Notification::factory()->create([
        'user_id'  => $medic->id,
        'is_read'  => false,
    ]);

    $this->actingAs($medic)
        ->patch(route('notifications.read', ['notification' => $notification->uuid]))
        ->assertRedirect();

    expect($notification->fresh()->is_read)->toBeTrue();
});

test('medic can mark all notifications as read', function () {
    $medic = User::factory()->medic()->create();
    Notification::factory()->count(3)->create([
        'user_id' => $medic->id,
        'is_read' => false,
    ]);

    $this->actingAs($medic)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect(Notification::where('user_id', $medic->id)->where('is_read', false)->count())->toBe(0);
});

test('medic cannot mark another user notification as read', function () {
    $medic = User::factory()->medic()->create();
    $otherUser = User::factory()->medic()->create();
    $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($medic)
        ->patch(route('notifications.read', ['notification' => $notification->uuid]))
        ->assertForbidden();
});
