<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferFactory extends Factory
{
    public function definition(): array
    {
        $warehouses = Warehouse::pluck('id')->toArray();

        return [
            'from_warehouse_id' => fake()->randomElement($warehouses),
            'to_warehouse_id' => function (array $attributes) {
                return Warehouse::where('id', '!=', $attributes['from_warehouse_id'])->first()->id;
            },
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
