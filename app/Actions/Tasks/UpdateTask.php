<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Actions\Notifications\CreateNotification;
use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use App\Models\User;
use App\Support\DashboardCache;
use Illuminate\Support\Facades\DB;

final class UpdateTask
{
    public function __construct(private readonly CreateNotification $notify) {}

    /**
     * Move or edit a task. The drop position is the fractional slot after
     * the neighbor the task lands on; without one it opens the column.
     * Reassigning a task notifies the new assignee.
     *
     * @param  array{project: Project, task: Task, column: ProjectColumn, user?: ?User, title?: string, description?: ?string, priority?: TaskPriority, assignee?: ?User, start_on?: ?string, due_on?: ?string, position_after?: ?float}  $input
     */
    public function handle(array $input): Task
    {
        $task = $input['task'];
        $previousAssigneeId = $task->assignee_id;

        $task = DB::transaction(function () use ($task, $input): Task {
            $position = $this->slotPosition($task, $input['column'], $input['position_after'] ?? null);

            $task->update([
                'column_id' => $input['column']->id,
                'title' => $input['title'] ?? $task->title,
                'description' => $input['description'] ?? $task->description,
                'priority' => ($input['priority'] ?? $task->priority)->value,
                'assignee_id' => array_key_exists('assignee', $input) ? $input['assignee']?->getKey() : $task->assignee_id,
                'start_on' => $input['start_on'] ?? $task->start_on?->toDateString(),
                'due_on' => $input['due_on'] ?? $task->due_on?->toDateString(),
                'position' => $position,
            ]);

            return $task;
        });

        DashboardCache::bust($input['project']->organization_id);

        if (array_key_exists('assignee', $input)
            && $input['assignee'] instanceof User
            && $input['assignee']->getKey() !== $previousAssigneeId
            && isset($input['user'])) {
            $this->notify->assignment($task, $input['user'], $input['assignee']);
        }

        return $task;
    }

    /**
     * The fractional position between the neighbor and the next task.
     */
    private function slotPosition(Task $task, ProjectColumn $column, ?float $positionAfter): float
    {
        if ($positionAfter === null) {
            $max = (float) Task::query()
                ->where('column_id', $column->id)
                ->whereKeyNot($task->id)
                ->max('position');

            return $max + 1000.0;
        }

        $next = (float) Task::query()
            ->where('column_id', $column->id)
            ->whereKeyNot($task->id)
            ->where('position', '>', $positionAfter)
            ->min('position');

        return $next === 0.0 ? $positionAfter + 1000.0 : ($positionAfter + $next) / 2.0;
    }
}
