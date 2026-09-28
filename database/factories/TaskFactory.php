<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'column_id' => fn (array $attributes) => ProjectColumn::factory()
                ->for(Project::find($attributes['project_id']))
                ->create()
                ->id,
            'title' => fake()->sentence(4),
            'created_by' => User::factory(),
        ];
    }
}
