<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_id' => 'EMP-'.fake()->unique()->numerify('####'),
            'position' => fake()->randomElement(['Cashier', 'Delivery Driver', 'Warehouse Staff']),
            'hire_date' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Staff $staff) {
            $staff->user->assignRole('staff');
        });
    }
}
