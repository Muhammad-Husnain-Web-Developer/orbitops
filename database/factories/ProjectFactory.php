<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Website Redesign', 'Mobile App', 'SaaS Dashboard', 'Brand System', 'Marketing Site']);

        return [
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)),
            'description' => fake()->sentence(12),
            'status' => ProjectStatus::Active,
            'priority' => 'medium',
            'color' => fake()->randomElement(['violet', 'blue', 'cyan', 'emerald', 'amber', 'rose']),
            'billing_type' => 'fixed',
            'budget' => fake()->numberBetween(5, 60) * 1000,
            'start_date' => now()->subWeeks(4)->toDateString(),
            'due_date' => now()->addWeeks(6)->toDateString(),
        ];
    }
}
