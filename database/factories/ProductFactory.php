<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $cost = $this->faker->randomFloat(2, 10, 500);
        $msrp = $cost * $this->faker->randomFloat(2, 1.5, 3.0);
        $retailPrice = $cost * $this->faker->randomFloat(2, 1.3, 2.5);

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['Electronics', 'Furniture', 'Clothing', 'Tools', 'Home & Garden']),
            'brand' => $this->faker->randomElement(['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4']),
            'vendor_id' => $this->faker->numberBetween(1, 10),
            'cost' => $cost,
            'msrp' => $msrp,
            'retail_price' => $retailPrice,
            'status' => $this->faker->randomElement(['active', 'discontinued', 'pending']),
        ];
    }
}
