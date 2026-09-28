<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ColumnCategory;
use App\Enums\ProjectVisibility;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class MyTasksController extends Controller
{
    /**
     * The signed-in member's open tasks, bucketed by date.
     */
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $organization->roleFor($user);

        $includedProjectIds = $organization->projects()
            ->whereHas('includedMembers', fn ($query) => $query->whereKey($user->id))
            ->pluck('id');

        $tasks = Task::query()
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
            ->where('projects.organization_id', $organization->id)
            ->where('tasks.assignee_id', $user->id)
            ->where('project_columns.category', '!=', ColumnCategory::Done->value)
            ->orderBy('tasks.due_on')
            ->orderBy('tasks.position')
            ->select('tasks.*')
            ->with(['project:id,organization_id,name,visibility', 'column:id,name,category'])
            ->get()
            ->filter(function (Task $task) use ($role, $includedProjectIds): bool {
                if ($task->project->visibility === ProjectVisibility::Open) {
                    return true;
                }

                if ($role?->canManage() === true) {
                    return true;
                }

                return $includedProjectIds->contains($task->project->id);
            })
            ->values();

        $today = Carbon::today(config('app.timezone'));

        $sections = [
            [
                'key' => 'overdue',
                'label' => 'Overdue',
                'tasks' => $tasks->filter(fn (Task $task): bool => $task->due_on !== null
                    && $task->due_on->startOfDay()->lt($today)),
            ],
            [
                'key' => 'today',
                'label' => 'Today',
                'tasks' => $tasks->filter(fn (Task $task): bool => $task->due_on !== null
                    && $task->due_on->startOfDay()->isSameDay($today)),
            ],
            [
                'key' => 'upcoming',
                'label' => 'Upcoming',
                'tasks' => $tasks->filter(fn (Task $task): bool => $task->due_on !== null
                    && $task->due_on->startOfDay()->gt($today)),
            ],
            [
                'key' => 'undated',
                'label' => 'No date',
                'tasks' => $tasks->filter(fn (Task $task): bool => $task->due_on === null),
            ],
        ];

        return Inertia::render('MyTasks/Index', [
            'sections' => array_map(
                fn (array $section): array => [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'tasks' => $section['tasks']->map(fn (Task $task): array => $this->taskPayload($task))->values()->all(),
                ],
                $sections,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function taskPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority->value,
            'due_on' => $task->due_on?->toDateString(),
            'project' => ['id' => $task->project->id, 'name' => $task->project->name],
            'column' => ['name' => $task->column->name, 'category' => $task->column->category->value],
            'project_id' => $task->project->id,
        ];
    }
}
