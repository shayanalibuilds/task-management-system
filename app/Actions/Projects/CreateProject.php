<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ColumnCategory;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\User;
use App\Support\DashboardCache;
use Illuminate\Support\Facades\DB;

final class CreateProject
{
    /**
     * @param  array{organization: Organization, name: string, color: string, icon: string, visibility: ProjectVisibility, creator: User}  $input
     */
    public function handle(array $input): Project
    {
        $project = DB::transaction(function () use ($input): Project {
            $project = Project::query()->create([
                'organization_id' => $input['organization']->id,
                'name' => $input['name'],
                'color' => $input['color'],
                'icon' => $input['icon'],
                'visibility' => $input['visibility'],
                'created_by' => $input['creator']->getKey(),
            ]);

            $this->seedDefaultColumns($project);

            return $project;
        });

        DashboardCache::bust($input['organization']->id);

        return $project;
    }

    /**
     * Every project starts with one column per category.
     */
    private function seedDefaultColumns(Project $project): void
    {
        $defaults = [
            ['name' => 'To do', 'category' => ColumnCategory::NotStarted, 'position' => 1000.0],
            ['name' => 'In progress', 'category' => ColumnCategory::InFlight, 'position' => 2000.0],
            ['name' => 'Done', 'category' => ColumnCategory::Done, 'position' => 3000.0],
        ];

        foreach ($defaults as $default) {
            ProjectColumn::query()->create([
                'project_id' => $project->id,
                ...$default,
            ]);
        }
    }
}
