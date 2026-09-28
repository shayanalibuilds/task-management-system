<?php

declare(strict_types=1);

use App\Enums\ColumnCategory;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function dashboard_project(Organization $organization, string $visibility = 'open'): array
{
    $project = Project::factory()->for($organization)->create(['visibility' => $visibility]);
    $todo = ProjectColumn::factory()->for($project)->create(['category' => ColumnCategory::NotStarted]);
    $inFlight = ProjectColumn::factory()->for($project)->create(['category' => ColumnCategory::InFlight]);
    $done = ProjectColumn::factory()->for($project)->create(['category' => ColumnCategory::Done]);

    return ['project' => $project, 'todo' => $todo, 'inFlight' => $inFlight, 'done' => $done];
}

test('the dashboard renders stats, today, distribution and recent projects', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    extract(dashboard_project($organization));

    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Due today',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today(),
    ]);
    Task::factory()->for($project)->create(['column_id' => $todo->id]);
    Task::factory()->for($project)->create(['column_id' => $inFlight->id]);
    Task::factory()->for($project)->create(['column_id' => $done->id]);

    $response = $this->actingAs($user)->get('/'.$organization->slug);
    $response->assertOk();

    $props = inertia_props($response);

    expect($props['stats']['open_tasks'])->toBe(3)
        ->and($props['stats']['due_today'])->toBe(1)
        ->and($props['stats']['completed'])->toBe(1)
        ->and($props['distribution'])->toBe([
            'not_started' => 2,
            'in_flight' => 1,
            'done' => 1,
        ])
        ->and($props['recent_projects'])->toHaveCount(1)
        ->and($props['recent_projects'][0]['name'])->toBe($project->name);
});

test('my due today and due soon tasks appear on the dashboard', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    extract(dashboard_project($organization));

    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Mine today',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today(),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Mine soon',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today()->addDays(3),
    ]);
    Task::factory()->for($project)->create([
        'column_id' => $todo->id,
        'title' => 'Later',
        'assignee_id' => $user->id,
        'due_on' => Carbon::today()->addDays(30),
    ]);

    $props = inertia_props($this->actingAs($user)->get('/'.$organization->slug));

    $todayTitles = array_map(fn (array $t) => $t['title'], $props['today']);
    $soonTitles = array_map(fn (array $t) => $t['title'], $props['due_soon']);

    expect($todayTitles)->toBe(['Mine today'])
        ->and($soonTitles)->toBe(['Mine soon']);
});

test('dashboard counts respect restricted project visibility', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member(OrganizationRole::Member);
    ['project' => $restricted] = dashboard_project($organization, 'restricted');

    Project::factory()->for($organization)->create();

    $column = ProjectColumn::factory()->for($restricted)->create();
    Task::factory()->for($restricted)->create(['column_id' => $column->id]);

    $props = inertia_props($this->actingAs($user)->get('/'.$organization->slug));

    expect($props['stats']['open_tasks'])->toBe(0)
        ->and($props['recent_projects'])->toHaveCount(1);
});

test('writing a task busts the dashboard cache', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();
    extract(dashboard_project($organization));

    $bucket = now()->format('Y-m-d');

    $this->actingAs($user)->get('/'.$organization->slug);

    $version = (int) Cache::get("dashboard:{$organization->id}:version", 0);

    expect(Cache::has("dashboard:{$organization->id}:{$user->id}:{$version}:{$bucket}:stats"))->toBeTrue();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', ['organization' => $organization, 'project' => $project]), [
            'column_id' => $todo->id,
            'title' => 'Buster',
        ])
        ->assertRedirect();

    $newVersion = (int) Cache::get("dashboard:{$organization->id}:version", 0);

    expect($newVersion)->toBeGreaterThan($version)
        ->and(Cache::has("dashboard:{$organization->id}:{$user->id}:{$newVersion}:{$bucket}:stats"))->toBeFalse();
});
