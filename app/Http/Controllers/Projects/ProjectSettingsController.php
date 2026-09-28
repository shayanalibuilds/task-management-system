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

final class ProjectSettingsController extends Controller
{
    public function __invoke(Request $request, Organization $organization, Project $project): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $project), 403);

        return Inertia::render('Projects/Settings', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'icon' => $project->icon,
                'visibility' => $project->visibility->value,
            ],
            'columns' => $project->columns()->get()->map(fn ($column): array => [
                'id' => $column->id,
                'name' => $column->name,
                'category' => $column->category->value,
                'position' => (float) $column->position,
                'tasks_count' => $column->tasks()->count(),
            ])->all(),
        ]);
    }
}
