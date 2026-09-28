<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Support\DashboardCache;

final class DeleteTask
{
    /**
     * @param  array{task: Task}  $input
     */
    public function handle(array $input): void
    {
        $input['task']->delete();

        DashboardCache::bust($input['task']->project_id);
    }
}
