<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowProjectController extends Controller
{
    /**
     * Restricted projects the user cannot see are 404, never 403.
     */
    public function __invoke(Request $request, Organization $organization, Project $project): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('view', $project), 404);

        $columns = $project->columns()
            ->with('tasks:id,project_id,column_id,title,priority,assignee_id,due_on,position')
            ->get()
            ->map(fn (ProjectColumn $column): array => [
                'id' => $column->id,
                'name' => $column->name,
                'category' => $column->category->value,
                'position' => (float) $column->position,
                'tasks' => $column->tasks->sortBy('position')->values()->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority->value,
                    'assignee_id' => $task->assignee_id,
                    'due_on' => $task->due_on?->toDateString(),
                    'position' => (float) $task->position,
                ])->all(),
            ]);

        $view = (string) $request->query('view', 'board');
        $openTask = $this->openTask($request, $project, $user);

        return Inertia::render('Projects/Show', [
            'view' => $view,
            'sheet' => $openTask,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'icon' => $project->icon,
                'visibility' => $project->visibility->value,
            ],
            'columns' => $columns,
            'can_manage' => $user->can('manage', $project),
            'members' => $project->organization->users()->orderBy('name')->get()->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
            ])->all(),
            'org_labels' => Label::query()->where('organization_id', $project->organization_id)->orderBy('name')->get()->map(fn (Label $label): array => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ])->all(),
        ]);
    }

    /**
     * Full detail for the right-hand sheet when ?task=id is open.
     *
     * @return array<string, mixed>|null
     */
    private function openTask(Request $request, Project $project, User $user): ?array
    {
        $taskId = $request->query('task');

        if ($taskId === null || ! ctype_digit((string) $taskId)) {
            return null;
        }

        $task = Task::query()
            ->where('project_id', $project->id)
            ->with(['column:id,name,category', 'assignee:id,name', 'labels:id,name,color', 'subtasks', 'comments.author:id,name'])
            ->find((int) $taskId);

        if ($task === null) {
            return null;
        }

        return [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'start_on' => $task->start_on?->toDateString(),
                'due_on' => $task->due_on?->toDateString(),
                'column' => [
                    'id' => $task->column->id,
                    'name' => $task->column->name,
                    'category' => $task->column->category->value,
                ],
                'assignee' => $task->assignee !== null
                    ? ['id' => $task->assignee->id, 'name' => $task->assignee->name]
                    : null,
                'labels' => $task->labels->map(fn ($label): array => [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                ])->values()->all(),
                'subtasks' => $task->subtasks->map(fn ($subtask): array => [
                    'id' => $subtask->id,
                    'title' => $subtask->title,
                    'completed' => $subtask->completed,
                ])->values()->all(),
                'comments' => $task->comments->map(fn ($comment): array => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => ['name' => $comment->author->name],
                    'created_at' => $comment->created_at->toISOString(),
                ])->values()->all(),
                'can_manage' => $user->can('manage', $task),
            ],
        ];
    }
}
