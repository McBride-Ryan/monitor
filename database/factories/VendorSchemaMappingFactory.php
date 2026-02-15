<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VendorSchemaMappingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_name' => $this->faker->randomElement(['acme_supply', 'global_parts', 'united_mfg']),
            'vendor_column' => $this->faker->unique()->word(),
            'erp_column' => $this->faker->word(),
            'transform_rule' => null,
        ];
    }
}
