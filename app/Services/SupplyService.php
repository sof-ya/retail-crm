<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    public function create(array $data): Supply
    {
        return DB::transaction(function () use ($data) {
            $supply = Supply::create([
                'warehouse_id' => $data['warehouse_id'],
                'created_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $supply->items()->create([
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);

                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id']);

                if ($stock->exists()) {
                    $stock->increment('stock', $item['count']);
                } else {
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'stock' => $item['count'],
                    ]);
                }

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'doc_type' => 'Supply',
                    'doc_id' => $supply->id,
                    'quantity' => $item['count'],
                    'created_at' => now(),
                ]);
            }

            return $supply;
        });
    }
}
