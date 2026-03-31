<?php

use App\Models\User;

test('guests are redirected from reports index', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});

test('medic can view reports index', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->has('summary')
            ->has('riskTrend')
        );
});

test('reports summary contains expected keys', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('summary.total_patients')
            ->has('summary.total_consultations')
            ->has('summary.total_risk_logs')
            ->has('summary.total_wearable_data')
        );
});
