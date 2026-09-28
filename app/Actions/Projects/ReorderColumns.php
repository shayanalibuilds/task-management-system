<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectColumn;

final class ReorderColumns
{
    /**
     * Reassign positions from left to right in the given order.
     *
     * @param  array{project: Project, columns: list<int>}  $input
     */
    public function handle(array $input): void
    {
        $position = 1000.0;

        foreach ($input['columns'] as $columnId) {
            ProjectColumn::query()
                ->where('project_id', $input['project']->id)
                ->whereKey($columnId)
                ->update(['position' => $position]);

            $position += 1000.0;
        }
    }
}
