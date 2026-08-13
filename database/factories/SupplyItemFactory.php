<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supply;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supply_id' => Supply::factory(),
            'product_id' => Product::factory(),
            'count' => fake()->numberBetween(1, 100),
        ];
    }
}
