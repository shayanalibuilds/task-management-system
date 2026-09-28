<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\CreateColumn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreColumnRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

final class StoreColumnController extends Controller
{
    public function __construct(private readonly CreateColumn $createColumn) {}

    public function __invoke(StoreColumnRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $this->createColumn->handle([
            'project' => $project,
            'name' => $validated['name'],
            'category' => $validated['category'],
        ]);

        return back()->with('success', 'Column added.');
    }
}
