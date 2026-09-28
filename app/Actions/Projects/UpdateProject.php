<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ProjectVisibility;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

final class UpdateProject
{
    /**
     * Opening a project to everyone clears the explicit include list.
     *
     * @param  array{project: Project, name: string, color: string, icon: string, visibility: ProjectVisibility}  $input
     */
    public function handle(array $input): Project
    {
        $project = $input['project'];

        return DB::transaction(function () use ($project, $input): Project {
            $project->update([
                'name' => $input['name'],
                'color' => $input['color'],
                'icon' => $input['icon'],
                'visibility' => $input['visibility'],
            ]);

            if ($input['visibility'] === ProjectVisibility::Open) {
                $project->includedMembers()->detach();
            }

            return $project;
        });
    }
}
