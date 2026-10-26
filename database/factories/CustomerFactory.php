<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+94 7# ### ####'),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'loyalty_points' => fake()->numberBetween(0, 500),
        ];
    }
}
