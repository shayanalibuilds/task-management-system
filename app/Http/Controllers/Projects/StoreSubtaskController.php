<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Subtasks\AddSubtask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subtasks\StoreSubtaskRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

final class StoreSubtaskController extends Controller
{
    public function __construct(private readonly AddSubtask $addSubtask) {}

    public function __invoke(StoreSubtaskRequest $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $this->addSubtask->handle([
            'task' => $task,
            'title' => $validated['title'],
        ]);

        return back()->with('success', 'Subtask added.');
    }
}
