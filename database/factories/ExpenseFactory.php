<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => ExpenseCategory::Software,
            'vendor' => fake()->company(),
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 20, 900),
            'currency' => 'USD',
            'spent_on' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'status' => ExpenseStatus::Pending,
        ];
    }
}
