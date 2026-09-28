<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Actions\Comments\AddComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreCommentController extends Controller
{
    public function __construct(private readonly AddComment $addComment) {}

    public function __invoke(StoreCommentRequest $request, Organization $organization, Project $project, Task $task): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $this->addComment->handle([
            'task' => $task,
            'author' => $user,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Comment added.');
    }
}
