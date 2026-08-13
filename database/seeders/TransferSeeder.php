<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transfer;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $transfers = Transfer::factory(10)->create();
        $products = Product::all();

        foreach ($transfers as $transfer) {
            $items = $products->random(rand(1, 3));

            foreach ($items as $product) {
                $transfer->items()->create([
                    'product_id' => $product->id,
                    'count' => rand(1, 50),
                ]);
            }
        }
    }
}
