<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\CreateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreProjectController extends Controller
{
    public function __construct(private readonly CreateProject $createProject) {}

    public function __invoke(StoreProjectRequest $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $project = $this->createProject->handle([
            'organization' => $organization,
            'name' => $validated['name'],
            'color' => $validated['color'],
            'icon' => $validated['icon'],
            'visibility' => $validated['visibility'],
            'creator' => $user,
        ]);

        return redirect()->route('projects.show', ['organization' => $organization, 'project' => $project]);
    }
}
