<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

test('a viewer sees the sheet read-only', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($viewer)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $project, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('sheet.task.id', $task->id)
                ->where('sheet.task.can_manage', false),
        );
});

test('a viewer cannot toggle or delete subtasks', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);
    $subtask = Subtask::factory()->for($task)->create();

    $this->actingAs($viewer)
        ->patch(route('projects.tasks.subtasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task->id, 'subtask' => $subtask->id]))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->delete(route('projects.tasks.subtasks.destroy', ['organization' => $organization, 'project' => $project, 'task' => $task->id, 'subtask' => $subtask->id]))
        ->assertForbidden();

    expect($subtask->fresh()->completed)->toBeFalse();
});

test('labels from another organization cannot attach', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $foreign = Organization::factory()->create();
    $foreignLabel = Label::factory()->for($foreign)->create();

    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->post(route('projects.tasks.labels.attach', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'label_id' => $foreignLabel->id,
        ])
        ->assertNotFound();

    expect($task->fresh()->labels->count())->toBe(0);
});

test('comments require a body', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($user)
        ->from(route('projects.show', ['organization' => $organization, 'project' => $project, 'task' => $task->id]))
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'body' => '',
        ])
        ->assertSessionHasErrors('body');

    expect(DB::table('task_comments')->count())->toBe(0);
});
