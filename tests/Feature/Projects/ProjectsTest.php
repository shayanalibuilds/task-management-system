<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Enums\ProjectVisibility;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;

test('an owner creates a project with categorized default columns', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();

    $response = $this->actingAs($owner)
        ->post(route('projects.store', $organization), [
            'name' => 'Website Relaunch',
            'color' => '#4F46E5',
            'icon' => 'rocket',
            'visibility' => 'open',
        ]);

    $project = Project::query()->where('name', 'Website Relaunch')->firstOrFail();

    $response->assertRedirect(route('projects.show', ['organization' => $organization, 'project' => $project]));

    expect($project->visibility)->toBe(ProjectVisibility::Open)
        ->and($project->organization_id)->toBe($organization->id)
        ->and($project->columns()->count())->toBe(3)
        ->and($project->columns->map(fn (ProjectColumn $column) => $column->category->value)->toArray())
        ->toEqual(['not_started', 'in_flight', 'done'])
        ->and($project->columns->map->position->toArray())->toEqual([1000.0, 2000.0, 3000.0]);
});

test('an admin creates projects but members and viewers do not', function (string $role, int $status): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::from($role));

    $this->actingAs($user)
        ->post(route('projects.store', $organization), [
            'name' => 'Side Project',
            'color' => '#4F46E5',
            'icon' => 'folder',
            'visibility' => 'open',
        ])
        ->assertStatus($status);

    expect(Project::query()->count())->toBe($status === 302 ? 1 : 0);
})->with([
    ['owner', 302],
    ['admin', 302],
    ['member', 403],
    ['viewer', 403],
]);

test('open projects are visible to every member but restricted only to included ones', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $member = attach_member($organization, OrganizationRole::Member);

    $open = Project::factory()->for($organization)->create(['visibility' => 'open']);
    $restricted = Project::factory()->for($organization)->create(['visibility' => 'restricted']);

    $this->actingAs($owner)->get(route('projects.show', ['organization' => $organization, 'project' => $restricted]))->assertOk();

    $this->actingAs($member)->get(route('projects.show', ['organization' => $organization, 'project' => $open]))->assertOk();

    $this->actingAs($member)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $restricted]))
        ->assertNotFound();
});

test('owners and admins always see restricted projects', function (): void {
    ['organization' => $organization] = organization_with_member();
    $admin = attach_member($organization, OrganizationRole::Admin);

    $restricted = Project::factory()->for($organization)->create(['visibility' => 'restricted']);

    $this->actingAs($admin)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $restricted]))
        ->assertOk();
});

test('projects from a foreign organization are not found', function (): void {
    ['user' => $member] = organization_with_member();
    $foreign = Organization::factory()->create();
    $project = Project::factory()->for($foreign)->create();

    $this->actingAs($member)
        ->get(route('projects.show', ['organization' => $foreign, 'project' => $project]))
        ->assertNotFound();
});

test('an owner updates project appearance and visibility', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();

    $this->actingAs($owner)
        ->patch(route('projects.update', ['organization' => $organization, 'project' => $project]), [
            'name' => 'Renamed Project',
            'color' => '#10B981',
            'icon' => 'sparkles',
            'visibility' => 'restricted',
        ])
        ->assertRedirect();

    expect($project->fresh()->name)->toBe('Renamed Project')
        ->and($project->fresh()->visibility)->toBe(ProjectVisibility::Restricted);
});

test('deleting a project removes its columns and tasks', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($owner)
        ->delete(route('projects.destroy', ['organization' => $organization, 'project' => $project]))
        ->assertRedirect(route('projects.index', $organization));

    expect(Project::query()->whereKey($project->id)->exists())->toBeFalse()
        ->and(ProjectColumn::query()->whereKey($column->id)->exists())->toBeFalse()
        ->and(Task::query()->where('project_id', $project->id)->exists())->toBeFalse();
});
