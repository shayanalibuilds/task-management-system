<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Enums\TaskPriority;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use App\Models\User;

test('a member creates a task in a column and it lands last', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::Member);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create(['position' => 1000.0]);
    Task::factory()->for($project)->create(['column_id' => $column->id, 'position' => 1000.0]);

    $this->actingAs($user)
        ->post(route('projects.tasks.store', ['organization' => $organization, 'project' => $project]), [
            'column_id' => $column->id,
            'title' => 'Draft the launch brief',
        ])
        ->assertRedirect();

    $task = Task::query()->where('title', 'Draft the launch brief')->firstOrFail();

    expect($task->column_id)->toBe($column->id)
        ->and($task->position)->toBe(2000.0)
        ->and($task->priority)->toBe(TaskPriority::None)
        ->and($task->created_by)->toBe($user->id);
});

test('a viewer cannot create or move tasks', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();

    $this->actingAs($viewer)
        ->post(route('projects.tasks.store', ['organization' => $organization, 'project' => $project]), [
            'column_id' => $column->id,
            'title' => 'Nope',
        ])
        ->assertForbidden();

    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($viewer)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => 'Nope',
        ])
        ->assertForbidden();
});

test('a task can move between columns and land between neighbors', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $first = ProjectColumn::factory()->for($project)->create(['position' => 1000.0]);
    $second = ProjectColumn::factory()->for($project)->create(['position' => 2000.0]);

    $target = Task::factory()->for($project)->create(['column_id' => $first->id, 'position' => 1000.0]);
    Task::factory()->for($project)->create(['column_id' => $second->id, 'position' => 1000.0]);
    Task::factory()->for($project)->create(['column_id' => $second->id, 'position' => 2000.0]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $target]), [
            'column_id' => $second->id,
            'title' => $target->title,
            'position_after' => 1000.0,
        ])
        ->assertRedirect();

    expect($target->fresh()->column_id)->toBe($second->id)
        ->and((float) $target->fresh()->position)->toBe(1500.0);
});

test('a task keeps its position when moving into an empty column', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $first = ProjectColumn::factory()->for($project)->create();
    $empty = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $first->id, 'position' => 1000.0]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $empty->id,
            'title' => $task->title,
        ])
        ->assertRedirect();

    expect($task->fresh()->column_id)->toBe($empty->id)
        ->and((float) $task->fresh()->position)->toBe(1000.0);
});

test('a task records priority, assignee and dates', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $assignee = attach_member($organization, OrganizationRole::Member);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => 'Updated title',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
            'start_on' => '2026-03-01',
            'due_on' => '2026-03-10',
        ])
        ->assertRedirect();

    expect($task->fresh()->title)->toBe('Updated title')
        ->and($task->fresh()->priority)->toBe(TaskPriority::High)
        ->and($task->fresh()->assignee_id)->toBe($assignee->id)
        ->and($task->fresh()->start_on->toDateString())->toBe('2026-03-01')
        ->and($task->fresh()->due_on->toDateString())->toBe('2026-03-10');
});

test('an assignee must belong to the organization', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);
    $outsider = User::factory()->create();

    $this->actingAs($user)
        ->from('/'.$organization->slug)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => $task->title,
            'assignee_id' => $outsider->id,
        ])
        ->assertSessionHasErrors('assignee_id');
});

test('a due date before the start date is rejected', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->from('/'.$organization->slug)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => $task->title,
            'start_on' => '2026-03-10',
            'due_on' => '2026-03-01',
        ])
        ->assertSessionHasErrors('due_on');
});

test('a task can be deleted', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->delete(route('projects.tasks.destroy', ['organization' => $organization, 'project' => $project, 'task' => $task]))
        ->assertRedirect();

    expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
});

test('tasks of a foreign project are not reachable', function (): void {
    ['user' => $user] = organization_with_member();
    $foreign = Organization::factory()->create();
    $project = Project::factory()->for($foreign)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->delete(route('projects.tasks.destroy', ['organization' => $foreign, 'project' => $project, 'task' => $task]))
        ->assertNotFound();
});
