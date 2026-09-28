<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\ReorderColumns;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ReorderColumnsRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

final class ReorderColumnsController extends Controller
{
    public function __construct(private readonly ReorderColumns $reorderColumns) {}

    public function __invoke(ReorderColumnsRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $this->reorderColumns->handle([
            'project' => $project,
            'columns' => $request->validated()['columns'],
        ]);

        return back();
    }
}
