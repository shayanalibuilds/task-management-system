<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Tasks\DeleteTask;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteTaskController extends Controller
{
    public function __construct(private readonly DeleteTask $deleteTask) {}

    public function __invoke(Request $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $task), 403);

        $this->deleteTask->handle(['task' => $task]);

        return back()->with('success', 'Task deleted.');
    }
}
