<?php

use App\Models\User;

test('guests are redirected from dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('medic can view dashboard', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});

test('dashboard returns patients and stats for medic', function () {
    $medic = User::factory()->medic()->create();
    User::factory()->patient()->count(3)->create();

    $this->actingAs($medic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('patients.data')
            ->has('stats')
            ->has('stats.total_patients')
            ->has('stats.high_risk_patients')
            ->has('stats.critical_patients')
            ->has('stats.pending_consultations')
        );
});

test('admin can view dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});

test('dashboard stats total_patients counts only patient role', function () {
    $medic = User::factory()->medic()->create();
    User::factory()->patient()->count(5)->create();

    $this->actingAs($medic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_patients', 5)
        );
});

test('patient can also access dashboard', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('patientStats')
            ->has('patientStats.total_consultations')
            ->has('patientStats.pending_consultations')
            ->has('patientStats.completed_consultations')
            ->has('recentConsultations')
        );
});
