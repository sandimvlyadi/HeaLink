<?php

use App\Models\User;
use App\Models\UserProfile;
use Laravel\Sanctum\Sanctum;

it('registers a new patient', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test Patient',
        'email' => 'patient@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['user' => ['uuid', 'name', 'email', 'role'], 'token'],
            'meta' => ['timestamp'],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.role', 'patient');

    $this->assertDatabaseHas('users', ['email' => 'patient@test.com']);
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'duplicate@test.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test',
        'email' => 'duplicate@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('fails registration with mismatched password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test',
        'email' => 'new@test.com',
        'password' => 'password',
        'password_confirmation' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->patient()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects login for inactive user', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertForbidden()
        ->assertJsonPath('success', false);
});

it('logs out', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('returns authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.uuid', $user->uuid)
        ->assertJsonMissing(['id']);
});

it('requires auth to access me', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('updates user profile', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/auth/profile', [
        'name' => 'Updated Name',
        'gender' => 'male',
        'job' => 'Software Engineer',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Updated Name');
});
