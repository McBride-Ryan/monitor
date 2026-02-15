<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'timestamp' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'amount' => $this->faker->randomFloat(2, 1, 9999.99),
            'description' => $this->faker->sentence(3),
            'account_type' => $this->faker->randomElement(['checking', 'savings', 'credit']),
            'order_origin' => $this->faker->randomElement(['Brand_1', 'Brand_2', 'Brand_3', 'Brand_4']),
        ];
    }
}
