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

final class ListProjectsController extends Controller
{
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $organization->roleFor($user);

        $projects = $organization->projects()
            ->with('organization')
            ->withCount('tasks')
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $project): bool => $user->can('view', $project))
            ->values();

        return Inertia::render('Projects/Index', [
            'projects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'icon' => $project->icon,
                'visibility' => $project->visibility->value,
                'tasks_count' => $project->tasks_count,
            ])->all(),
            'can_create' => $role?->canManage() ?? false,
        ]);
    }
}
