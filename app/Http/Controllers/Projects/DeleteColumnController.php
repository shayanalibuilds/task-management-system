<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Projects\ColumnHasTasks;
use App\Actions\Projects\DeleteColumn;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DeleteColumnController extends Controller
{
    public function __construct(private readonly DeleteColumn $deleteColumn) {}

    public function __invoke(Request $request, Organization $organization, Project $project, ProjectColumn $column): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $project), 403);

        try {
            $this->deleteColumn->handle(['column' => $column]);
        } catch (ColumnHasTasks $exception) {
            throw ValidationException::withMessages([
                'column' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Column deleted.');
    }
}
