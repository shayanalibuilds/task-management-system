<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ColumnCategory;
use App\Models\Project;
use App\Models\ProjectColumn;

final class CreateColumn
{
    /**
     * New columns land after everything else on the board.
     *
     * @param  array{project: Project, name: string, category: ColumnCategory}  $input
     */
    public function handle(array $input): ProjectColumn
    {
        $max = (float) $input['project']->columns()->max('position');

        return ProjectColumn::query()->create([
            'project_id' => $input['project']->id,
            'name' => $input['name'],
            'category' => $input['category'],
            'position' => $max + 1000.0,
        ]);
    }
}
