<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransferService
{
    /**
     * Создать перемещение: списать с отправителя, приход на получателя, записать движения.
     *
     * @param  array{from_warehouse_id: int, to_warehouse_id: int, items: array<int, array{product_id: int, count: int}>}  $data
     * @return \App\Models\Transfer|\Illuminate\Http\JsonResponse
     */
    public function create(array $data): Transfer|JsonResponse
    {
        $unavailableProducts = [];

        // проверка количества товаров на складе отгрузки: если товара недостаточно, возвращается ошибка
        foreach ($data['items'] as $item) {
            $stock = Stock::where('product_id', $item['product_id'])
                ->where('warehouse_id', $data['from_warehouse_id'])
                ->first();

            if (!$stock || $stock->stock < $item['count']) {
                $unavailableProducts[] = [
                    'product_id' => $item['product_id'],
                    'required' => $item['count'],
                    'available' => $stock ? $stock->stock : 0,
                ];
            }
        }

        if (!empty($unavailableProducts)) {
            return response()->json([
                'message' => 'Some products are not available in sufficient quantity.',
                'unavailable_products' => $unavailableProducts,
            ], 422);
        }

        return DB::transaction(function () use ($data) {
            $transfer = Transfer::create([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'created_at' => now(),
            ]);

            // каждый товар из склада отгрузки записываем в перемещение 
            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);

                Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['from_warehouse_id'])
                    ->decrement('stock', $item['count']);

                $targetStock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['to_warehouse_id']);

                // если на принимающем складе есть остатки товара, то увеличить количество. иначе, создать новую запись остатков 
                if ($targetStock->exists()) {
                    $targetStock->increment('stock', $item['count']);
                } else {
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $data['to_warehouse_id'],
                        'stock' => $item['count'],
                    ]);
                }

                // создаётся две записи о движении товаров: о списании на складе отгрузки и о пополнении на складе приемки 
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $data['from_warehouse_id'],
                    'doc_type' => Transfer::class,
                    'doc_id' => $transfer->id,
                    'quantity' => -$item['count'],
                    'created_at' => now(),
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $data['to_warehouse_id'],
                    'doc_type' => Transfer::class,
                    'doc_id' => $transfer->id,
                    'quantity' => $item['count'],
                    'created_at' => now(),
                ]);
            }

            return $transfer;
        });
    }
}
