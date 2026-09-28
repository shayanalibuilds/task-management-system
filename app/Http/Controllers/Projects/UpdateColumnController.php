<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\UpdateColumn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\UpdateColumnRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use Illuminate\Http\RedirectResponse;

final class UpdateColumnController extends Controller
{
    public function __construct(private readonly UpdateColumn $updateColumn) {}

    public function __invoke(UpdateColumnRequest $request, Organization $organization, Project $project, ProjectColumn $column): RedirectResponse
    {
        $validated = $request->validated();

        $this->updateColumn->handle([
            'column' => $column,
            'name' => $validated['name'],
            'category' => $validated['category'],
        ]);

        return back()->with('success', 'Column updated.');
    }
}
