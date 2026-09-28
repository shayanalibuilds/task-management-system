<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Subtasks\DeleteSubtask;
use App\Actions\Subtasks\ToggleSubtask;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SubtaskController extends Controller
{
    public function __construct(
        private readonly ToggleSubtask $toggleSubtask,
        private readonly DeleteSubtask $deleteSubtask,
    ) {}

    public function update(Request $request, Organization $organization, Project $project, Task $task, Subtask $subtask): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $task), 403);

        $this->toggleSubtask->handle(['subtask' => $subtask]);

        return back()->with('success', 'Subtask updated.');
    }

    public function destroy(Request $request, Organization $organization, Project $project, Task $task, Subtask $subtask): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $task), 403);

        $this->deleteSubtask->handle(['subtask' => $subtask]);

        return back()->with('success', 'Subtask deleted.');
    }
}
