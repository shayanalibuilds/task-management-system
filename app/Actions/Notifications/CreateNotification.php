<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;

/**
 * Creates inbox rows. Email delivery is gated per member in notification
 * settings; inbox rows themselves are never deleted by preferences.
 */
final class CreateNotification
{
    /**
     * @param  array{organization_id: int, user_id: int, type: string, task_id?: ?int, actor_id?: ?int, message: string}  $input
     */
    public function handle(array $input): ?Notification
    {
        return Notification::query()->create([
            'organization_id' => $input['organization_id'],
            'user_id' => $input['user_id'],
            'type' => $input['type'],
            'task_id' => $input['task_id'] ?? null,
            'actor_id' => $input['actor_id'] ?? null,
            'message' => $input['message'],
        ]);
    }

    /**
     * Notify the new assignee of a task, unless they made the change.
     */
    public function assignment(Task $task, User $actor, User $assignee): void
    {
        if ($assignee->getKey() === $actor->getKey()) {
            return;
        }

        $this->handle([
            'organization_id' => $task->project->organization_id,
            'user_id' => $assignee->getKey(),
            'type' => Notification::TYPE_ASSIGNMENT,
            'task_id' => $task->id,
            'actor_id' => $actor->getKey(),
            'message' => $actor->name.' assigned you '.$task->title,
        ]);
    }

    /**
     * Notify the assignee that someone commented on their task.
     */
    public function comment(Task $task, User $actor): void
    {
        if ($task->assignee_id === null || $task->assignee_id === $actor->getKey()) {
            return;
        }

        $this->handle([
            'organization_id' => $task->project->organization_id,
            'user_id' => $task->assignee_id,
            'type' => Notification::TYPE_COMMENT,
            'task_id' => $task->id,
            'actor_id' => $actor->getKey(),
            'message' => $actor->name.' commented on '.$task->title,
        ]);
    }
}
