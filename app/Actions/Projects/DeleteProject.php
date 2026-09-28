<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use App\Support\DashboardCache;

final class DeleteProject
{
    /**
     * Columns and tasks fall with the project through cascade deletes.
     *
     * @param  array{project: Project}  $input
     */
    public function handle(array $input): void
    {
        $organizationId = $input['project']->organization_id;
        $input['project']->delete();

        DashboardCache::bust($organizationId);
    }
}
