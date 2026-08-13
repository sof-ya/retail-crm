<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transfer_id' => Transfer::factory(),
            'product_id' => Product::factory(),
            'count' => fake()->numberBetween(1, 50),
        ];
    }
}
