<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Tasks\UpdateTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

final class UpdateTaskController extends Controller
{
    public function __construct(private readonly UpdateTask $updateTask) {}

    public function __invoke(UpdateTaskRequest $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        /** @var ProjectColumn $column */
        $column = ProjectColumn::query()->findOrFail($validated['column_id']);

        $this->updateTask->handle([
            'project' => $project,
            'task' => $task,
            'column' => $column,
            ...$validated,
        ]);

        return back()->with('success', 'Task updated.');
    }
}
