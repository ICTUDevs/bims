<?php

use App\Models\Permission;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

it('allows users with reports.export to view the reports page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('counts')->has('projects'));
});

it('loads reports page even when audit_logs.view permission row is missing', function () {
    Permission::query()->where('name', 'audit_logs.view')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('canViewAuditLogs', false));
});

it('denies users without reports.export', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $user->syncPermissions(['dashboard.access']);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertForbidden();
});
