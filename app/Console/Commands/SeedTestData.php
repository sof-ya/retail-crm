<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class SeedTestData extends Command
{
    protected $signature = 'app:seed-test-data';
    protected $description = 'Fill products, warehouses, stocks and customers with test data';

    public function handle(): void
    {
        $products = Product::factory(20)->create();
        $this->info("Created {$products->count()} products");

        $warehouses = Warehouse::factory(5)->create();
        $this->info("Created {$warehouses->count()} warehouses");

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => rand(0, 500),
                ]);
            }
        }
        $this->info("Created stocks for all products in all warehouses");

        $customers = Customer::factory(20)->create();
        $this->info("Created {$customers->count()} customers");

        $this->info('Test data seeded successfully!');
    }
}
