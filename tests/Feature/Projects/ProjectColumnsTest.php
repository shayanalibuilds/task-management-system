<?php

declare(strict_types=1);

use App\Enums\ColumnCategory;
use App\Enums\OrganizationRole;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;

test('an admin adds a column and it lands in the right category', function (): void {
    ['organization' => $organization, 'user' => $admin] = organization_with_member(OrganizationRole::Admin);
    $project = Project::factory()->for($organization)->create();
    ProjectColumn::factory()->for($project)->create(['position' => 1000.0]);
    ProjectColumn::factory()->for($project)->create(['position' => 2000.0]);
    ProjectColumn::factory()->for($project)->create(['position' => 3000.0]);

    $this->actingAs($admin)
        ->post(route('projects.columns.store', ['organization' => $organization, 'project' => $project]), [
            'name' => 'In Review',
            'category' => 'in_flight',
        ])
        ->assertRedirect();

    $column = $project->columns()->where('name', 'In Review')->firstOrFail();

    expect($column->category)->toBe(ColumnCategory::InFlight)
        ->and($column->position)->toBe(4000.0);
});

test('members and viewers cannot manage columns', function (string $role): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::from($role));
    $project = Project::factory()->for($organization)->create();

    $this->actingAs($user)
        ->post(route('projects.columns.store', ['organization' => $organization, 'project' => $project]), [
            'name' => 'Blocked',
            'category' => 'not_started',
        ])
        ->assertForbidden();

    expect($project->columns()->count())->toBe(0);
})->with([
    ['member'],
    ['viewer'],
]);

test('a column can be renamed and recategorized', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create([
        'name' => 'Doing',
        'category' => 'in_flight',
    ]);

    $this->actingAs($owner)
        ->patch(route('projects.columns.update', ['organization' => $organization, 'project' => $project, 'column' => $column]), [
            'name' => 'Building',
            'category' => 'not_started',
        ])
        ->assertRedirect();

    expect($column->fresh()->name)->toBe('Building')
        ->and($column->fresh()->category)->toBe(ColumnCategory::NotStarted);
});

test('a column holding tasks cannot be deleted', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($owner)
        ->from(route('projects.settings', ['organization' => $organization, 'project' => $project]))
        ->delete(route('projects.columns.destroy', ['organization' => $organization, 'project' => $project, 'column' => $column]))
        ->assertSessionHasErrors('column');

    expect($column->fresh()->exists())->toBeTrue();
});

test('an empty column can be deleted', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();

    $this->actingAs($owner)
        ->delete(route('projects.columns.destroy', ['organization' => $organization, 'project' => $project, 'column' => $column]))
        ->assertRedirect();

    expect(ProjectColumn::query()->whereKey($column->id)->exists())->toBeFalse();
});

test('columns can be reordered with fractional positions', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $project = Project::factory()->for($organization)->create();

    $first = ProjectColumn::factory()->for($project)->create(['position' => 1000.0]);
    $second = ProjectColumn::factory()->for($project)->create(['position' => 2000.0]);
    $third = ProjectColumn::factory()->for($project)->create(['position' => 3000.0]);

    $this->actingAs($owner)
        ->patch(route('projects.columns.reorder', ['organization' => $organization, 'project' => $project]), [
            'columns' => [$third->id, $second->id, $first->id],
        ])
        ->assertRedirect();

    expect($third->fresh()->position)->toBe(1000.0)
        ->and($second->fresh()->position)->toBe(2000.0)
        ->and($first->fresh()->position)->toBe(3000.0);
});
