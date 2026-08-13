<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'status' => fake()->randomElement(['active', 'completed', 'canceled']),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'completed_at' => fn (array $attributes) => $attributes['status'] === 'completed'
                ? fake()->dateTimeBetween('-1 month', 'now')
                : null,
        ];
    }
}
