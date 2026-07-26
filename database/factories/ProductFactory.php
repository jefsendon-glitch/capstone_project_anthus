<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $category = fake()->randomElement(['refill', 'alkaline', 'distilled', 'purified', 'mineral', 'container']);
        $size = fake()->randomElement(['5 Gallon', '3 Gallon', '1 Liter', '500ml']);

        return [
            'name' => ucfirst($category).' Water — '.$size,
            'category' => $category,
            'size' => $size,
            'unit_price' => fake()->randomFloat(2, 15, 250),
            'stock_quantity' => fake()->numberBetween(20, 200),
            'low_stock_threshold' => 10,
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => 5, 'low_stock_threshold' => 10]);
    }
}
