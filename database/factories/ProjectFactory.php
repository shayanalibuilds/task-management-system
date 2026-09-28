<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectVisibility;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(asText: true),
            'color' => fake()->randomElement(['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9']),
            'icon' => 'folder',
            'visibility' => ProjectVisibility::Open,
            'created_by' => User::factory(),
        ];
    }

    public function restricted(): static
    {
        return $this->state(fn (): array => [
            'visibility' => ProjectVisibility::Restricted,
        ]);
    }
}
