<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => InvoiceStatus::Draft,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
        ];
    }
}
