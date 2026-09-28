<?php

declare(strict_types=1);

namespace App\Actions\Comments;

use App\Enums\ProjectVisibility;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Mentions only reach members who can actually see the task.
 */
final class NotifyMentionedUsers
{
    /**
     * @param  array{task: Task, actor: User, mention_ids: list<int>}  $input
     */
    public function handle(array $input): void
    {
        $task = $input['task'];
        $actor = $input['actor'];
        $project = $task->project;

        $visible = $project->visibility === ProjectVisibility::Open;
        $organization = $project->organization;

        foreach ($input['mention_ids'] as $userId) {
            if ($userId === $actor->getKey()) {
                continue;
            }

            $mentioned = User::query()->find($userId);

            if ($mentioned === null) {
                continue;
            }

            if ($visible) {
                $canSee = $organization->hasMember($userId);
            } else {
                $canSee = Gate::forUser($mentioned)->allows('view', $project);
            }

            if (! $canSee) {
                continue;
            }

            Notification::query()->create([
                'organization_id' => $project->organization_id,
                'user_id' => $userId,
                'type' => 'mention',
                'task_id' => $task->id,
                'actor_id' => $actor->getKey(),
                'message' => $actor->name.' mentioned you on '.$task->title,
            ]);
        }
    }
}
