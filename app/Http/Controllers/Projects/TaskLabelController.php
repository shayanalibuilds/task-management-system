<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Labels\AttachLabel;
use App\Actions\Labels\CreateLabel;
use App\Actions\Labels\DetachLabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\CreateLabelRequest;
use App\Models\Label;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TaskLabelController extends Controller
{
    public function __construct(
        private readonly AttachLabel $attachLabel,
        private readonly DetachLabel $detachLabel,
        private readonly CreateLabel $createLabel,
    ) {}

    public function store(Request $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $task), 403);

        $validated = $request->validate([
            'label_id' => ['required', 'integer'],
        ]);

        $label = Label::query()
            ->where('organization_id', $organization->id)
            ->where('id', $validated['label_id'])
            ->first();

        abort_if($label === null, 404);

        $this->attachLabel->handle(['task' => $task, 'label' => $label]);

        return back()->with('success', 'Label attached.');
    }

    public function create(CreateLabelRequest $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $label = $this->createLabel->handle([
            'organization' => $organization,
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        $task->labels()->syncWithoutDetaching([$label->id]);

        return back()->with('success', 'Label created.');
    }

    public function destroy(Request $request, Organization $organization, Project $project, Task $task, Label $label): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $task), 403);

        $this->detachLabel->handle(['task' => $task, 'label' => $label]);

        return back()->with('success', 'Label removed.');
    }
}
