<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

final class UpdateProjectController extends Controller
{
    public function __construct(private readonly UpdateProject $updateProject) {}

    public function __invoke(UpdateProjectRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $this->updateProject->handle([
            'project' => $project,
            'name' => $validated['name'],
            'color' => $validated['color'],
            'icon' => $validated['icon'],
            'visibility' => $validated['visibility'],
        ]);

        return back()->with('success', 'Project updated.');
    }
}
