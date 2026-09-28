<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;

final class DeleteProject
{
    /**
     * Columns and tasks fall with the project through cascade deletes.
     *
     * @param  array{project: Project}  $input
     */
    public function handle(array $input): void
    {
        $input['project']->delete();
    }
}
