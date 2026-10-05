<?php

namespace Database\Factories;

use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Discovery', 'Wireframes', 'Visual Design', 'Development', 'Launch']),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'status' => 'pending',
        ];
    }
}
