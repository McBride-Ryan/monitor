<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'asset_type' => $this->faker->randomElement(['image', 'video', 'document']),
            'url' => $this->faker->imageUrl(640, 480, 'products', true),
            'alt_text' => $this->faker->optional(0.8)->sentence(6),
            'is_active' => $this->faker->boolean(90),
            'last_checked_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
