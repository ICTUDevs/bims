<?php

use App\Models\Project;
use App\Models\Training;
use App\Models\User;

it('creates a training', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('trainings.store'), [
        'training_title' => 'Livelihood orientation',
        'training_type' => 'Orientation',
        'facilitator' => 'Jane Doe',
        'venue' => 'Barangay hall',
        'date_conducted' => '2026-05-31',
        'duration_hours' => 12,
        'project_id' => '',
    ]);

    $response->assertRedirect(route('trainings.index'));
    expect(Training::where('training_title', 'Livelihood orientation')->exists())->toBeTrue();
});

it('creates a training with a linked project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post(route('trainings.store'), [
        'training_title' => 'With project',
        'training_type' => 'Workshop',
        'facilitator' => 'Jane',
        'venue' => 'Hall',
        'date_conducted' => '2026-05-31',
        'duration_hours' => 12,
        'project_id' => $project->id,
    ]);

    $response->assertRedirect(route('trainings.index'));
    expect(Training::where('training_title', 'With project')->value('project_id'))->toBe($project->id);
});

it('rejects an invalid project id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('trainings.store'), [
        'training_title' => 'Invalid project',
        'training_type' => 'Workshop',
        'date_conducted' => '2026-05-31',
        'project_id' => 'not-a-valid-uuid',
    ]);

    $response->assertSessionHasErrors('project_id');
});

it('accepts the legacy training_tile field name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('trainings.store'), [
        'training_tile' => 'Legacy title field',
        'training_type' => 'Orientation',
        'date_conducted' => '2026-05-31',
    ]);

    $response->assertRedirect(route('trainings.index'));
    expect(Training::where('training_title', 'Legacy title field')->exists())->toBeTrue();
});
