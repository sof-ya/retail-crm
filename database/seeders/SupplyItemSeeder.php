<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supply;
use App\Models\SupplyItem;
use Illuminate\Database\Seeder;

class SupplyItemSeeder extends Seeder
{
    public function run(): void
    {
        $supplies = Supply::all();
        $products = Product::all();

        foreach ($supplies as $supply) {
            $items = $products->random(rand(1, 5));

            foreach ($items as $product) {
                SupplyItem::create([
                    'supply_id' => $supply->id,
                    'product_id' => $product->id,
                    'count' => rand(1, 100),
                ]);
            }
        }
    }
}
