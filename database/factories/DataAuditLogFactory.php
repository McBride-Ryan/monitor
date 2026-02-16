<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DataAuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'audit_type' => $this->faker->randomElement(['price_discrepancy', 'broken_asset', 'orphaned_product', 'inventory_ghost']),
            'severity' => $this->faker->randomElement(['info', 'warning', 'critical']),
            'entity_type' => $this->faker->randomElement(['Product', 'InventoryItem', 'ProductAsset']),
            'entity_id' => $this->faker->numberBetween(1, 100),
            'details' => [
                'message' => $this->faker->sentence(),
                'value' => $this->faker->randomFloat(2, 1, 1000),
            ],
            'resolved_at' => $this->faker->optional(0.3)->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
