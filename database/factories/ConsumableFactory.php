<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Consumable>
 */
class ConsumableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Carbon Filter', 'UV Lamp', 'Chlorine Tablets', 'Bottle Caps', 'Shrink Wrap']),
            'category' => fake()->randomElement(['bottle_caps', 'bottle_seals', 'labels', 'plastic_bags', 'water_filters', 'uv_lamps', 'cleaning_supplies']),
            'unit' => fake()->randomElement(['pcs', 'packs', 'liters', 'kg']),
            'quantity' => fake()->numberBetween(10, 100),
            'low_stock_threshold' => 5,
            'unit_cost' => fake()->randomFloat(2, 5, 100),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['quantity' => 2, 'low_stock_threshold' => 5]);
    }
}
