<?php

declare(strict_types=1);

namespace App\Actions\Subtasks;

use App\Models\Subtask;
use App\Models\Task;

final class AddSubtask
{
    /**
     * Subtasks land at the bottom of their parent's list.
     *
     * @param  array{task: Task, title: string}  $input
     */
    public function handle(array $input): Subtask
    {
        $position = (float) Subtask::query()
            ->where('task_id', $input['task']->id)
            ->max('position');

        return Subtask::query()->create([
            'task_id' => $input['task']->id,
            'title' => $input['title'],
            'completed' => false,
            'position' => $position + 1000.0,
        ]);
    }
}
