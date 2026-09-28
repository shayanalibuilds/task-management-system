<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateTask
{
    /**
     * New tasks land at the bottom of the requested column.
     *
     * @param  array{project: Project, column: ProjectColumn, title: string, user: User, priority?: TaskPriority, assignee?: ?User, description?: ?string, start_on?: ?string, due_on?: ?string}  $input
     */
    public function handle(array $input): Task
    {
        return DB::transaction(function () use ($input): Task {
            $position = (float) Task::query()
                ->where('column_id', $input['column']->id)
                ->max('position');

            return Task::query()->create([
                'project_id' => $input['project']->id,
                'column_id' => $input['column']->id,
                'title' => $input['title'],
                'description' => $input['description'] ?? null,
                'priority' => ($input['priority'] ?? TaskPriority::None)->value,
                'assignee_id' => $input['assignee']?->getKey(),
                'start_on' => $input['start_on'] ?? null,
                'due_on' => $input['due_on'] ?? null,
                'position' => $position + 1000.0,
                'created_by' => $input['user']->getKey(),
            ]);
        });
    }
}
