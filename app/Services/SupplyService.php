<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    /**
     * Создать поставку: увеличить остатки и записать движения.
     *
     * @param  array{warehouse_id: int, items: array<int, array{product_id: int, count: int}>}  $data
     * @return \App\Models\Supply
     */
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

                // если товар уже есть на складе, то обновить его остатки. иначе, создать запись об остатках
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
                    'doc_type' => Supply::class,
                    'doc_id' => $supply->id,
                    'quantity' => $item['count'],
                    'created_at' => now(),
                ]);
            }

            return $supply;
        });
    }
}
