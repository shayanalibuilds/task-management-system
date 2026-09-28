<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\ProjectColumn;

final class DeleteColumn
{
    /**
     * Deleting a column would orphan its tasks; move them first.
     *
     * @param  array{column: ProjectColumn}  $input
     *
     * @throws ColumnHasTasks when any task still lives in the column
     */
    public function handle(array $input): void
    {
        $column = $input['column'];

        $count = $column->tasks()->count();

        if ($count > 0) {
            throw new ColumnHasTasks("Move {$count} task(s) out of {$column->name} before deleting it.");
        }

        $column->delete();
    }
}
