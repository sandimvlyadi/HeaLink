<?php

use App\Models\ChatHistory;
use App\Models\User;

test('guests are redirected from patients index', function () {
    $this->get(route('patients.index'))->assertRedirect(route('login'));
});

test('medic can view patients index', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('patients/index'));
});

test('patients index returns paginated patients', function () {
    $medic = User::factory()->medic()->create();
    User::factory()->patient()->count(3)->create();

    $this->actingAs($medic)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/index')
            ->has('patients.data')
        );
});

test('medic can view patient detail', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();

    $this->actingAs($medic)
        ->get(route('patients.show', ['user' => $patient->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('patients/show'));
});

test('patient detail initial load omits deferred props', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();

    $this->actingAs($medic)
        ->get(route('patients.show', ['user' => $patient->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/show')
            ->has('patient')
            ->missing('wearableHistory')
            ->missing('sleepHistory')
            ->missing('riskHistory')
        );
});

test('patient detail deferred props resolve on partial reload', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    $version = (string) hash_file('xxh128', public_path('build/manifest.json'));

    $response = $this->actingAs($medic)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'patients/show',
            'X-Inertia-Partial-Data' => 'wearableHistory,sleepHistory,riskHistory',
        ])
        ->getJson(route('patients.show', ['user' => $patient->uuid]));

    $response->assertOk();
    $response->assertJsonStructure(['props' => ['wearableHistory', 'sleepHistory', 'riskHistory']]);
});

test('medic can view patient chat log', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();
    ChatHistory::factory()->count(3)->create(['user_id' => $patient->id]);

    $this->actingAs($medic)
        ->get(route('patients.chat-log', ['user' => $patient->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/chat-log')
            ->where('patient.uuid', $patient->uuid)
            ->has('chatHistories.data')
        );
});

test('patient response exposes uuid not id', function () {
    $medic = User::factory()->medic()->create();
    $patient = User::factory()->patient()->create();

    $this->actingAs($medic)
        ->get(route('patients.show', ['user' => $patient->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/show')
            ->where('patient.uuid', $patient->uuid)
            ->missing('patient.id')
        );
});
