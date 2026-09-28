<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ColumnCategory;
use App\Enums\ProjectVisibility;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\DashboardCache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Organization home: stats, today, distribution, due soon and recent
     * projects. Aggregates are cached briefly per member and busted by
     * the actions that write tasks and projects.
     */
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $organization->roleFor($user);
        $cache = new DashboardCache($organization->id, $user->id);

        $includedProjectIds = $organization->projects()
            ->whereHas('includedMembers', fn ($query) => $query->whereKey($user->id))
            ->pluck('id');

        $visibleProjects = $organization->projects()
            ->with('organization')
            ->get()
            ->filter(function (Project $project) use ($role, $includedProjectIds): bool {
                if ($project->visibility === ProjectVisibility::Open) {
                    return true;
                }

                if ($role?->canManage() === true) {
                    return true;
                }

                return $includedProjectIds->contains($project->id);
            })
            ->values();

        $projectIds = $visibleProjects->pluck('id');

        $today = Carbon::today(config('app.timezone'));

        $stats = $cache->remember('stats', function () use ($projectIds, $today): array {
            $base = Task::query()->whereIn('tasks.project_id', $projectIds);

            $open = (clone $base)
                ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                ->where('project_columns.category', '!=', ColumnCategory::Done->value)
                ->count();

            $dueToday = (clone $base)
                ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                ->where('project_columns.category', '!=', ColumnCategory::Done->value)
                ->whereDate('tasks.due_on', $today->toDateString())
                ->count();

            $overdue = (clone $base)
                ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                ->where('project_columns.category', '!=', ColumnCategory::Done->value)
                ->whereNotNull('tasks.due_on')
                ->whereDate('tasks.due_on', '<', $today->toDateString())
                ->count();

            $completed = (clone $base)
                ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                ->where('project_columns.category', '=', ColumnCategory::Done->value)
                ->count();

            return [
                'open_tasks' => $open,
                'due_today' => $dueToday,
                'overdue' => $overdue,
                'completed' => $completed,
            ];
        });

        $distribution = $cache->remember('distribution', function () use ($projectIds): array {
            $rows = Task::query()
                ->whereIn('tasks.project_id', $projectIds)
                ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                ->selectRaw('project_columns.category as category, count(*) as total')
                ->groupBy('project_columns.category')
                ->pluck('total', 'category');

            return [
                'not_started' => (int) ($rows[ColumnCategory::NotStarted->value] ?? 0),
                'in_flight' => (int) ($rows[ColumnCategory::InFlight->value] ?? 0),
                'done' => (int) ($rows[ColumnCategory::Done->value] ?? 0),
            ];
        });

        $mine = fn () => Task::query()
            ->whereIn('tasks.project_id', $projectIds)
            ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
            ->where('tasks.assignee_id', $user->id)
            ->where('project_columns.category', '!=', ColumnCategory::Done->value)
            ->orderBy('tasks.due_on')
            ->select('tasks.*');

        $todayTasks = $cache->remember('today', function () use ($mine, $today): array {
            return $mine()
                ->whereDate('tasks.due_on', $today->toDateString())
                ->with('project:id,name')
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_on' => $task->due_on?->toDateString(),
                    'priority' => $task->priority->value,
                    'project_id' => $task->project->id,
                    'project' => $task->project->name,
                ])->values()->all();
        });

        $dueSoon = $cache->remember('due_soon', function () use ($mine, $today): array {
            return $mine()
                ->whereDate('tasks.due_on', '>', $today->toDateString())
                ->whereDate('tasks.due_on', '<=', $today->copy()->addDays(7)->toDateString())
                ->with('project:id,name')
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_on' => $task->due_on?->toDateString(),
                    'priority' => $task->priority->value,
                    'project_id' => $task->project->id,
                    'project' => $task->project->name,
                ])->values()->all();
        });

        $recentProjects = $cache->remember('recent_projects', function () use ($visibleProjects, $user): array {
            return $visibleProjects
                ->sortByDesc('updated_at')
                ->take(4)
                ->map(function (Project $project) use ($user): array {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'color' => $project->color,
                        'icon' => $project->icon,
                        'visibility' => $project->visibility->value,
                        'open_tasks' => $project->tasks()
                            ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
                            ->where('project_columns.category', '!=', ColumnCategory::Done->value)
                            ->count(),
                        'can_manage' => $user->can('manage', $project),
                    ];
                })
                ->values()
                ->all();
        });

        return Inertia::render('Dashboard/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => $role?->value,
                'member_count' => $organization->memberships()->count(),
            ],
            'stats' => $stats,
            'distribution' => $distribution,
            'today' => $todayTasks,
            'due_soon' => $dueSoon,
            'recent_projects' => $recentProjects,
        ]);
    }
}
