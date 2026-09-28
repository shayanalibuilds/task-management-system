<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Label;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskComment;
use Inertia\Testing\AssertableInertia;

function sheet_project(OrganizationRole $role = OrganizationRole::Owner): array
{
    ['organization' => $organization, 'user' => $user] = organization_with_member($role);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id]);

    return ['organization' => $organization, 'user' => $user, 'project' => $project, 'column' => $column, 'task' => $task];
}

test('the sheet loads task details when task is in the query', function (): void {
    extract(sheet_project());

    $this->actingAs($user)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $project, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('sheet.task.id', $task->id)
                ->where('sheet.task.title', $task->title)
                ->where('sheet.task.column.id', $column->id)
                ->has('sheet.task.subtasks', 0)
                ->has('sheet.task.comments', 0)
                ->has('sheet.task.labels', 0),
        );
});

test('the sheet stays closed without a task query', function (): void {
    extract(sheet_project());

    $this->actingAs($user)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $project]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('sheet', null),
        );
});

test('a member comments on a task', function (): void {
    extract(sheet_project(OrganizationRole::Member));

    $this->actingAs($user)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'body' => 'Started on this today.',
        ])
        ->assertRedirect();

    $comment = TaskComment::query()->firstOrFail();

    expect($comment->user_id)->toBe($user->id)
        ->and($comment->body)->toBe('Started on this today.');
});

test('a viewer cannot comment', function (): void {
    extract(sheet_project(OrganizationRole::Viewer));

    $this->actingAs($user)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'body' => 'Viewer voice',
        ])
        ->assertForbidden();

    expect(TaskComment::query()->count())->toBe(0);
});

test('mentions notify only members who can see the task', function (): void {
    ['organization' => $organization, 'user' => $author] = organization_with_member();

    $open = Project::factory()->for($organization)->create(['visibility' => 'open']);
    $restricted = Project::factory()->for($organization)->restricted()->create();

    $openColumn = ProjectColumn::factory()->for($open)->create();
    $restrictedColumn = ProjectColumn::factory()->for($restricted)->create();

    $openTask = Task::factory()->for($open)->create(['column_id' => $openColumn->id]);
    $restrictedTask = Task::factory()->for($restricted)->create(['column_id' => $restrictedColumn->id]);

    $included = attach_member($organization, OrganizationRole::Member);
    $excluded = attach_member($organization, OrganizationRole::Member);
    $restricted->includedMembers()->attach($included->id);

    $this->actingAs($author)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $open, 'task' => $openTask->id]), [
            'body' => "Pinging @[Included Person](user:{$included->id}) and @[Excluded Person](user:{$excluded->id})",
        ])
        ->assertRedirect();

    $this->actingAs($author)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $restricted, 'task' => $restrictedTask->id]), [
            'body' => "Quiet ping @[Included Person](user:{$included->id}) and @[Excluded Person](user:{$excluded->id})",
        ])
        ->assertRedirect();

    $openNotified = Notification::query()
        ->where('task_id', $openTask->id)
        ->pluck('user_id')->map(intval(...))->toArray();
    $restrictedNotified = Notification::query()
        ->where('task_id', $restrictedTask->id)
        ->pluck('user_id')->map(intval(...))->toArray();

    expect(in_array($included->id, $openNotified, true))->toBeTrue()
        ->and(in_array($excluded->id, $openNotified, true))->toBeTrue()
        ->and(in_array($included->id, $restrictedNotified, true))->toBeTrue()
        ->and(in_array($excluded->id, $restrictedNotified, true))->toBeFalse();
});

test('the author is not notified by their own mention', function (): void {
    extract(sheet_project());

    $this->actingAs($user)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'body' => "Note to self @[Me](user:{$user->id})",
        ])
        ->assertRedirect();

    expect(Notification::query()->count())->toBe(0);
});

test('subtasks can be added, toggled and removed', function (): void {
    extract(sheet_project());

    $this->actingAs($user)
        ->post(route('projects.tasks.subtasks.store', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'title' => 'Draft outline',
        ])
        ->assertRedirect();

    $subtask = Subtask::query()->firstOrFail();

    expect($subtask->completed)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('projects.tasks.subtasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task->id, 'subtask' => $subtask->id]))
        ->assertRedirect();

    expect($subtask->fresh()->completed)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('projects.tasks.subtasks.destroy', ['organization' => $organization, 'project' => $project, 'task' => $task->id, 'subtask' => $subtask->id]))
        ->assertRedirect();

    expect(Subtask::query()->whereKey($subtask->id)->exists())->toBeFalse();
});

test('labels attach, detach and admins create new ones', function (): void {
    extract(sheet_project());
    $label = Label::factory()->for($organization)->create(['name' => 'Design']);

    $this->actingAs($user)
        ->post(route('projects.tasks.labels.attach', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'label_id' => $label->id,
        ])
        ->assertRedirect();

    expect($task->fresh()->labels->pluck('name')->all())->toBe(['Design']);

    $this->actingAs($user)
        ->delete(route('projects.tasks.labels.detach', ['organization' => $organization, 'project' => $project, 'task' => $task->id, 'label' => $label->id]))
        ->assertRedirect();

    expect($task->fresh()->labels->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('projects.tasks.labels.create', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'name' => 'Research',
            'color' => '#10B981',
        ])
        ->assertRedirect();

    expect(Label::query()->where('name', 'Research')->exists())->toBeTrue()
        ->and($task->fresh()->labels->pluck('name')->all())->toBe(['Research']);
});

test('a member cannot create labels but can attach existing ones', function (): void {
    extract(sheet_project(OrganizationRole::Member));
    $label = Label::factory()->for($organization)->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.labels.create', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'name' => 'Blocked',
            'color' => '#EF4444',
        ])
        ->assertForbidden();

    expect(Label::query()->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('projects.tasks.labels.attach', ['organization' => $organization, 'project' => $project, 'task' => $task->id]), [
            'label_id' => $label->id,
        ])
        ->assertRedirect();

    expect($task->fresh()->labels->count())->toBe(1);
});

test('restricted task sheets are hidden from excluded members', function (): void {
    ['organization' => $organization] = organization_with_member();
    $member = attach_member($organization, OrganizationRole::Member);

    $restricted = Project::factory()->for($organization)->restricted()->create();
    $column = ProjectColumn::factory()->for($restricted)->create();
    $task = Task::factory()->for($restricted)->create(['column_id' => $column->id]);

    $this->actingAs($member)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $restricted, 'task' => $task->id]))
        ->assertNotFound();
});
