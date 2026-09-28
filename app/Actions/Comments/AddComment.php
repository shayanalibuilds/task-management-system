<?php

declare(strict_types=1);

namespace App\Actions\Comments;

use App\Actions\Notifications\CreateNotification;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AddComment
{
    public function __construct(
        private NotifyMentionedUsers $notifyMentionedUsers,
        private CreateNotification $notify,
    ) {}

    /**
     * Store the comment and notify mentioned members who can see the task.
     *
     * @param  array{task: Task, author: User, body: string}  $input
     */
    public function handle(array $input): TaskComment
    {
        $task = $input['task'];
        $author = $input['author'];
        $project = $task->project;
        $organization = $project->organization;

        $mentionIds = MentionParser::extract(
            $input['body'],
            $organization,
        );

        return DB::transaction(function () use ($task, $author, $input, $mentionIds): TaskComment {
            $comment = TaskComment::query()->create([
                'task_id' => $task->id,
                'user_id' => $author->getKey(),
                'body' => $input['body'],
                'mentions' => $mentionIds,
            ]);

            $this->notifyMentionedUsers->handle([
                'task' => $task,
                'actor' => $author,
                'mention_ids' => $mentionIds,
            ]);

            if ($task->assignee_id !== null && ! in_array($task->assignee_id, $mentionIds, true)) {
                $this->notify->comment($task, $author);
            }

            return $comment;
        });
    }
}
