<?php

declare(strict_types=1);

use App\Enums\ColumnCategory;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

function column_for(Project $project, ColumnCategory $category): ProjectColumn
{
    return ProjectColumn::factory()->for($project)->create([
        'category' => $category,
        'position' => 5000.0,
    ]);
}

test('the list view renders grouped rows', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create(['column_id' => $column->id, 'title' => 'Listed task']);

    $this->actingAs($user)
        ->get(route('projects.show', ['organization' => $organization, 'project' => $project]).'?view=list')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Projects/Show')
                ->where('view', 'list')
                ->has('columns.0.tasks.0', fn (AssertableInertia $task): AssertableInertia => $task
                    ->where('title', 'Listed task')
                    ->etc()),
        );
});

test('my tasks shows only my open tasks in date buckets', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $other = attach_member($organization, OrganizationRole::Member);
    $project = Project::factory()->for($organization)->create();
    $todo = column_for($project, ColumnCategory::NotStarted);
    $done = column_for($project, ColumnCategory::Done);

    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Overdue task',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today()->subDay(),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Today task',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today(),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Upcoming task',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today()->addDays(3),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Undated task',
        'assignee_id' => $user->id,
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $done->id,
        'title' => 'Finished task',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today(),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Someone else task',
        'assignee_id' => $other->id,
        'due_on' => Carbon::today(),
    ]);

    $this->actingAs($user)
        ->get(route('my-tasks.show', $organization))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('MyTasks/Index')
                ->has('sections', 4)
                ->where('sections.0.key', 'overdue')
                ->where('sections.0.tasks.0.title', 'Overdue task')
                ->where('sections.1.key', 'today')
                ->where('sections.1.tasks.0.title', 'Today task')
                ->where('sections.2.key', 'upcoming')
                ->where('sections.2.tasks.0.title', 'Upcoming task')
                ->where('sections.3.key', 'undated')
                ->where('sections.3.tasks.0.title', 'Undated task'),
        );
});

test('my tasks hides restricted projects the user cannot see', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::Member);

    $open = Project::factory()->for($organization)->create(['visibility' => 'open', 'created_by' => $user->id]);
    $restricted = Project::factory()->for($organization)->restricted()->create(['created_by' => $user->id]);

    $openColumn = column_for($open, ColumnCategory::NotStarted);
    $restrictedColumn = column_for($restricted, ColumnCategory::NotStarted);

    Task::factory()->for($open)->create([
        'column_id' => $openColumn->id,
        'title' => 'Open project task',
        'assignee_id' => $user->id,
    ]);
    Task::factory()->for($restricted)->create([
        'column_id' => $restrictedColumn->id,
        'title' => 'Hidden task',
        'assignee_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('my-tasks.show', $organization))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('MyTasks/Index')
                ->has('sections.0.tasks', 1)
                ->where('sections.0.tasks.0.title', 'Open project task'),
        );
});

test('the org calendar places tasks in the right month cells', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create([
        'column_id' => $column->id,
        'title' => 'Mid month task',
        'assignee_id' => $user->id,
        'due_on' => '2026-03-15',
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $column->id,
        'title' => 'Other month task',
        'assignee_id' => $user->id,
        'due_on' => '2026-04-02',
    ]);

    $this->actingAs($user)
        ->get(route('calendar.show', ['organization' => $organization, 'month' => '2026-03']))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Calendar/Index')
                ->where('month_label', 'March 2026')
                ->where('prev_month', '2026-02')
                ->where('next_month', '2026-04')
                ->has('weeks.0.0', fn (AssertableInertia $day): AssertableInertia => $day
                    ->has('date')
                    ->has('tasks')
                    ->etc()),
        );
});

test('calendar cells hold the tasks due that day', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    $project = Project::factory()->for($organization)->create();
    $column = ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create([
        'column_id' => $column->id,
        'title' => 'Mid month task',
        'assignee_id' => $user->id,
        'due_on' => '2026-03-15',
    ]);

    $response = $this->actingAs($user)
        ->get(route('calendar.show', ['organization' => $organization, 'month' => '2026-03']));

    $response->assertOk();

    $inertia = $response->viewData('page');
    /** @var array<string, mixed> $props */
    $props = json_decode((string) ($inertia->page ?? '{}'), true);

    $titles = [];

    foreach (($props['props']['weeks'] ?? []) as $week) {
        foreach ($week as $day) {
            if (($day['date'] ?? '') === '2026-03-15') {
                foreach (($day['tasks'] ?? []) as $task) {
                    $titles[] = $task['title'];
                }
            }
        }
    }

    expect($titles)->toBe(['Mid month task']);
});

test('calendar tasks respect project visibility', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::Member);

    $restricted = Project::factory()->for($organization)->restricted()->create(['created_by' => $user->id]);
    $restrictedColumn = column_for($restricted, ColumnCategory::NotStarted);
    Task::factory()->for($restricted)->create([
        'column_id' => $restrictedColumn->id,
        'title' => 'Hidden calendar task',
        'assignee_id' => $user->id,
        'due_on' => '2026-03-15',
    ]);

    $response = $this->actingAs($user)
        ->get(route('calendar.show', ['organization' => $organization, 'month' => '2026-03']));

    $response->assertOk();

    $inertia = $response->viewData('page');
    /** @var array<string, mixed> $props */
    $props = json_decode((string) ($inertia->page ?? '{}'), true);

    $titles = [];
    foreach (($props['props']['weeks'] ?? []) as $week) {
        foreach ($week as $day) {
            foreach (($day['tasks'] ?? []) as $task) {
                $titles[] = $task['title'];
            }
        }
    }

    expect($titles)->not->toContain('Hidden calendar task');
});

test('calendar requests from a foreign organization are not found', function (): void {
    ['user' => $user] = organization_with_member();
    $foreign = Organization::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar.show', ['organization' => $foreign, 'month' => '2026-03']))
        ->assertNotFound();
});
