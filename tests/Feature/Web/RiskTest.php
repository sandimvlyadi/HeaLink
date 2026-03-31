<?php

use App\Models\User;

test('guests are redirected from risk dashboard', function () {
    $this->get(route('risk.index'))->assertRedirect(route('login'));
});

test('medic can view risk dashboard', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('risk.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('risk/dashboard')
            ->has('criticalPatients')
            ->has('highRiskPatients')
            ->has('riskDistribution')
            ->has('thresholds')
        );
});

test('admin can view risk dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('risk.index'))
        ->assertOk();
});
