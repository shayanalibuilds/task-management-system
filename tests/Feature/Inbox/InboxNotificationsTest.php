<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

function inbox_fixture(OrganizationRole $authorRole = OrganizationRole::Owner): array
{
    ['organization' => $organization, 'user' => $owner] = organization_with_member(OrganizationRole::Owner);
    $assignee = attach_member($organization, OrganizationRole::Member);
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create(['column_id' => $column->id, 'assignee_id' => $assignee->id]);

    return ['organization' => $organization, 'owner' => $owner, 'assignee' => $assignee, 'project' => $project, 'column' => $column, 'task' => $task];
}

test('assigning a task notifies the new assignee', function (): void {
    extract(inbox_fixture());
    $member = attach_member($organization, OrganizationRole::Member);

    $this->actingAs($owner)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => $task->title,
            'assignee_id' => $member->id,
        ])
        ->assertRedirect();

    $notification = Notification::query()->where('user_id', $member->id)->firstOrFail();

    expect($notification->type)->toBe('assignment')
        ->and($notification->actor_id)->toBe($owner->id)
        ->and($notification->task_id)->toBe($task->id);
});

test('unassigning does not notify', function (): void {
    extract(inbox_fixture());

    $this->actingAs($owner)
        ->patch(route('projects.tasks.update', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'column_id' => $column->id,
            'title' => $task->title,
            'assignee_id' => null,
        ])
        ->assertRedirect();

    expect(Notification::query()->count())->toBe(0);
});

test('commenting on an assigned task notifies the assignee', function (): void {
    extract(inbox_fixture());

    $this->actingAs($owner)
        ->post(route('projects.tasks.comments.store', ['organization' => $organization, 'project' => $project, 'task' => $task]), [
            'body' => 'How is this going?',
        ])
        ->assertRedirect();

    $notification = Notification::query()->where('user_id', $assignee->id)->firstOrFail();

    expect($notification->type)->toBe('comment')
        ->and($notification->actor_id)->toBe($owner->id);
});

test('the inbox renders unread rows and mark all read clears them', function (): void {
    extract(inbox_fixture());

    Notification::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $assignee->id,
        'type' => 'assignment',
        'task_id' => $task->id,
        'actor_id' => $owner->id,
        'message' => 'Assigned to you',
    ]);

    $this->actingAs($assignee)
        ->get(route('inbox.index', $organization))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('unread_count', 1)
                ->has('notifications', 1),
        );

    $this->actingAs($assignee)
        ->post(route('inbox.mark_all', $organization))
        ->assertRedirect();

    expect(Notification::query()->whereNull('read_at')->count())->toBe(0);

    $this->actingAs($assignee)
        ->get(route('inbox.index', $organization))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('unread_count', 0)
                ->has('notifications', 1),
        );
});

test('the due reminder command is idempotent', function (): void {
    extract(inbox_fixture());

    $this->actingAs($assignee);

    Task::query()->whereKey($task->id)->update(['due_on' => Carbon::today()]);

    $this->artisan('tasks:due-reminders')->assertSuccessful();
    $this->artisan('tasks:due-reminders')->assertSuccessful();

    expect(Notification::query()->where('type', 'due_reminder')->count())->toBe(1);
});

test('due reminders skip done tasks and unassigned tasks', function (): void {
    ['organization' => $organization, 'owner' => $owner, 'assignee' => $assignee, 'project' => $project, 'column' => $column] = inbox_fixture();

    $doneColumn = ProjectColumn::factory()->for($project)->create(['category' => 'done']);
    Task::factory()->for($project)->create([
        'column_id' => $doneColumn->id,
        'assignee_id' => $assignee->id,
        'due_on' => Carbon::today(),
    ]);

    Task::factory()->for($project)->create([
        'column_id' => $column->id,
        'due_on' => Carbon::today(),
    ]);

    $this->artisan('tasks:due-reminders')->assertSuccessful();

    expect(Notification::query()->where('type', 'due_reminder')->count())->toBe(0);
});

test('email toggles update prefs without deleting inbox rows', function (): void {
    extract(inbox_fixture());

    Notification::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $assignee->id,
        'type' => 'assignment',
        'task_id' => $task->id,
        'actor_id' => $owner->id,
        'message' => 'Assigned to you',
    ]);

    $this->actingAs($assignee)
        ->patch(route('settings.notifications.update', $organization), [
            'email_mentions' => false,
            'email_assignments' => false,
            'email_comments' => true,
            'email_due_reminders' => false,
        ])
        ->assertRedirect();

    $prefs = $organization->memberships()
        ->where('user_id', $assignee->id)
        ->firstOrFail()
        ->email_prefs;

    expect(is_array($prefs) ? $prefs['mention'] : null)->toBeFalse()
        ->and($prefs['assignment'])->toBeFalse()
        ->and($prefs['comment'])->toBeTrue()
        ->and($prefs['due_reminder'])->toBeFalse()
        ->and(Notification::query()->where('user_id', $assignee->id)->count())->toBe(1);
});
