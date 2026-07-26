<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_name' => fake()->randomElement(['RO Machine #1', 'UV Sterilizer', 'Ozone Generator']),
            'category' => fake()->randomElement(['filter', 'pump', 'uv_lamp', 'ozone', 'ro_membrane']),
            'last_maintenance_date' => now()->subMonths(2),
            'next_due_date' => now()->addMonth(),
            'status' => 'ok',
        ];
    }
}
