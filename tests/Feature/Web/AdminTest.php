<?php

use App\Models\User;

test('guests are redirected from admin users page', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

test('admin can view user management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users')
            ->has('users.data')
        );
});

test('medic is forbidden from admin users page', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('patient is forbidden from admin users page', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('admin can view statistics page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.statistics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/statistics')
            ->has('usersByRole')
            ->has('consultationsByStatus')
            ->has('riskByLevel')
            ->has('newPatientsPerMonth')
        );
});

test('medic is forbidden from admin statistics page', function () {
    $medic = User::factory()->medic()->create();

    $this->actingAs($medic)
        ->get(route('admin.statistics'))
        ->assertForbidden();
});
