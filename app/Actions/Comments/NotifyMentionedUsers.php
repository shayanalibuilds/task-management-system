<?php

declare(strict_types=1);

namespace App\Actions\Comments;

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

        $visible = $task->project->visibility === 'open';

        foreach ($input['mention_ids'] as $userId) {
            if ($userId === $actor->getKey()) {
                continue;
            }

            $mentioned = User::query()->find($userId);

            if ($mentioned === null) {
                continue;
            }

            $canSee = $visible
                ? $task->project->organization->hasMember($userId)
                : Gate::forUser($mentioned)->allows('view', $task->project);

            if (! $canSee) {
                continue;
            }

            Notification::query()->create([
                'organization_id' => $task->project->organization_id,
                'user_id' => $userId,
                'type' => 'mention',
                'task_id' => $task->id,
                'actor_id' => $actor->getKey(),
                'message' => $actor->name.' mentioned you on '.$task->title,
            ]);
        }
    }
}
