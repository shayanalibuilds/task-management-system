<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ColumnCategory;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily nudges for tasks due today. Running it twice in a day never
 * duplicates a reminder, so re-runs and retries are safe.
 */
final class SendDueReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tasks:due-reminders
        {--dry-run : Count the reminders without writing them}';

    /**
     * @var string
     */
    protected $description = 'Create due-today inbox reminders for task assignees';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today()->toDateString();
        $created = 0;
        $skipped = 0;

        $tasks = Task::query()
            ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
            ->whereDate('tasks.due_on', $today)
            ->where('project_columns.category', '!=', ColumnCategory::Done->value)
            ->whereNotNull('tasks.assignee_id')
            ->select('tasks.*')
            ->with('project:id,organization_id')
            ->get();

        foreach ($tasks as $task) {
            $assignee = User::query()->find($task->assignee_id);

            if ($assignee === null) {
                continue;
            }

            $alreadyReminded = Notification::query()
                ->where('user_id', $assignee->id)
                ->where('task_id', $task->id)
                ->where('type', Notification::TYPE_DUE_REMINDER)
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadyReminded) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                Notification::query()->create([
                    'organization_id' => $task->project->organization_id,
                    'user_id' => $assignee->id,
                    'type' => Notification::TYPE_DUE_REMINDER,
                    'task_id' => $task->id,
                    'actor_id' => null,
                    'message' => $task->title.' is due today',
                ]);
            }

            $created++;
        }

        $this->info($dryRun
            ? "Would create {$created} reminder(s), skip {$skipped} duplicate(s)."
            : "Created {$created} reminder(s), skipped {$skipped} duplicate(s).");

        return self::SUCCESS;
    }
}
