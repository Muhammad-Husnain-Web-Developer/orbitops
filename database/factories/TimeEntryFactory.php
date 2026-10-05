<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subDays(fake()->numberBetween(0, 10))->setTime(fake()->numberBetween(9, 14), 0);

        return [
            'description' => fake()->sentence(4),
            'started_at' => $start,
            'ended_at' => $start->copy()->addMinutes(fake()->numberBetween(30, 240)),
            'billable' => true,
        ];
    }
}
