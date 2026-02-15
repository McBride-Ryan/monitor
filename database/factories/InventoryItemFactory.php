<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_location' => $this->faker->randomElement(['A-1', 'B-2', 'C-3', 'D-4', 'E-5']),
            'qty_on_hand' => $this->faker->numberBetween(0, 1000),
            'qty_committed' => $this->faker->numberBetween(0, 100),
            'ecommerce_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'low_stock']),
            'last_synced_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
