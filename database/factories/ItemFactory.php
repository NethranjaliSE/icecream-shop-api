<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucwords(fake()->words(2, true)).' '.fake()->randomElement(['Scoop', 'Cone', 'Tub (500ml)', 'Tub (1L)']),
            'sku' => fake()->unique()->bothify('IC-####-??'),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1, 25),
            'quantity' => fake()->numberBetween(20, 200),
            'reorder_level' => 10,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 2,
            'reorder_level' => 10,
        ]);
    }
}
