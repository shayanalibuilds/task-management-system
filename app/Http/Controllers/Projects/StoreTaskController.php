<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Tasks\CreateTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreTaskController extends Controller
{
    public function __construct(private readonly CreateTask $createTask) {}

    public function __invoke(StoreTaskRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        /** @var ProjectColumn $column */
        $column = ProjectColumn::query()->findOrFail($validated['column_id']);

        $this->createTask->handle([
            'project' => $project,
            'column' => $column,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'assignee' => $validated['assignee'],
            'start_on' => $validated['start_on'],
            'due_on' => $validated['due_on'],
            'user' => $user,
        ]);

        return back()->with('success', 'Task created.');
    }
}
