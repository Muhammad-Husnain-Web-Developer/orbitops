<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'industry' => fake()->randomElement(['Media', 'Healthcare', 'E-commerce', 'Finance', 'Education']),
            'status' => ClientStatus::Active,
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => 'https://'.fake()->domainName(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'currency' => 'USD',
        ];
    }
}
