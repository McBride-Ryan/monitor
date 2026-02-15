<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrandComplianceRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4']),
            'rule_type' => $this->faker->randomElement(['required_field', 'naming_convention', 'price_range']),
            'rule_config' => ['field' => 'sku', 'pattern' => '/^[A-Z]{3}-\d+$/'],
            'is_active' => true,
        ];
    }
}
