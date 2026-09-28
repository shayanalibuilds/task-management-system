<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProjectVisibility;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    /**
     * A month grid of due dates across the organization's visible projects.
     * Dates render from strings the server computed, so the client never
     * has to parse a date and shift a day across timezones.
     */
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();
        $role = $organization->roleFor($user);

        $monthParam = (string) $request->query('month', '');

        try {
            $month = Carbon::parse($monthParam !== '' ? $monthParam.'-01' : 'today')->startOfDay();

            if ($month->format('Y-m') !== $monthParam) {
                $month = Carbon::today()->startOfDay();
            }
        } catch (\Throwable) {
            $month = Carbon::today()->startOfDay();
        }

        $includedProjectIds = $organization->projects()
            ->whereHas('includedMembers', fn ($query) => $query->whereKey($user->id))
            ->pluck('id');

        $tasks = Task::query()
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->join('project_columns', 'project_columns.id', '=', 'tasks.column_id')
            ->where('projects.organization_id', $organization->id)
            ->where('project_columns.category', '!=', 'done')
            ->whereNotNull('tasks.due_on')
            ->whereBetween('tasks.due_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('tasks.due_on')
            ->select('tasks.*')
            ->with(['project:id,organization_id,name,visibility', 'column:id,name,category'])
            ->get()
            ->filter(function (Task $task) use ($role, $includedProjectIds): bool {
                if ($task->project->visibility === ProjectVisibility::Open) {
                    return true;
                }

                if ($role?->canManage() === true) {
                    return true;
                }

                return $includedProjectIds->contains($task->project->id);
            });

        $tasksByDate = $tasks->groupBy(fn (Task $task): string => $task->due_on->toDateString());

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $cursor = $gridStart->copy();
        $today = Carbon::today()->toDateString();

        while ($cursor->lessThanOrEqualTo($gridEnd)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->toDateString();
                $week[] = [
                    'date' => $date,
                    'in_month' => $cursor->month === $month->month,
                    'is_today' => $date === $today,
                    'tasks' => $tasksByDate->get($date, collect())
                        ->map(fn (Task $task): array => [
                            'id' => $task->id,
                            'title' => $task->title,
                            'project' => $task->project->name,
                            'priority' => $task->priority->value,
                            'column_category' => $task->column->category->value,
                            'project_id' => $task->project->id,
                        ])->all(),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return Inertia::render('Calendar/Index', [
            'weeks' => $weeks,
            'month_label' => $month->format('F Y'),
            'month_key' => $month->format('Y-m'),
            'prev_month' => $month->copy()->subMonth()->format('Y-m'),
            'next_month' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
