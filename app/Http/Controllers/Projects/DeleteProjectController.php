<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\DeleteProject;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteProjectController extends Controller
{
    public function __construct(private readonly DeleteProject $deleteProject) {}

    public function __invoke(Request $request, Organization $organization, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $project), 403);

        $this->deleteProject->handle(['project' => $project]);

        return redirect()->route('projects.index', $organization);
    }
}
