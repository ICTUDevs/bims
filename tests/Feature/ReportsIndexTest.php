<?php

use App\Models\User;

it('allows users with reports.export to view the reports page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('counts')->has('projects'));
});

it('denies users without reports.export', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->syncPermissions(['dashboard.access']);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertForbidden();
});
