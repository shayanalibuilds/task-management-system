<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
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
            ->with('tasks:id,project_id,column_id,title')
            ->get()
            ->map(fn ($column): array => [
                'id' => $column->id,
                'name' => $column->name,
                'category' => $column->category->value,
                'position' => (float) $column->position,
                'tasks' => $column->tasks->map(fn ($task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                ])->all(),
            ]);

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'icon' => $project->icon,
                'visibility' => $project->visibility->value,
            ],
            'columns' => $columns,
            'can_manage' => $user->can('manage', $project),
        ]);
    }
}
