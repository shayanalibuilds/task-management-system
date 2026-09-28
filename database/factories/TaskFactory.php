<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\ProjectColumn;
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
                ->for(Project::query()->whereKey($attributes['project_id'])->firstOrFail())
                ->create()
                ->id,
            'title' => fake()->sentence(4),
            'priority' => TaskPriority::None,
            'position' => 1000.0,
            'created_by' => User::factory(),
        ];
    }
}
