<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'industry' => fake()->randomElement(['Creative Agency', 'Software Company', 'Consultancy', 'Design Studio']),
            'owner_id' => User::factory(),
            'accent' => fake()->randomElement(Workspace::ACCENTS),
            'currency' => 'USD',
            'invoice_prefix' => 'INV',
            'payment_terms' => 14,
        ];
    }
}
