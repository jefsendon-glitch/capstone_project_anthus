<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_code' => 'CUS-'.fake()->unique()->numerify('######'),
            'notes' => null,
            'is_blacklisted' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Customer $customer) {
            $customer->user->assignRole('customer');
        });
    }
}
