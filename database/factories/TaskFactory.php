<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Design onboarding flow', 'Write API docs', 'QA checkout', 'Set up analytics', 'Review copy']),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_date' => now()->addDays(fake()->numberBetween(1, 20))->toDateString(),
        ];
    }
}
