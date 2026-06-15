<?php

use App\Models\User;

it('allows users with dashboard access to view monitoring', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('access')->has('activeTab'));
});

it('denies users without dashboard access', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->syncPermissions(['beneficiaries.view']);

    $response = $this->actingAs($user)->get(route('monitoring.index'));

    $response->assertForbidden();
});
