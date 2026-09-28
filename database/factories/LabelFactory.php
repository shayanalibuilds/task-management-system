<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Label;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Label>
 */
final class LabelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement(['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9']),
        ];
    }
}
