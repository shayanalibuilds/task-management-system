<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ColumnCategory;
use App\Models\Project;
use App\Models\ProjectColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectColumn>
 */
final class ProjectColumnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->unique()->word(),
            'category' => ColumnCategory::NotStarted,
            'position' => 1000.0,
        ];
    }
}
