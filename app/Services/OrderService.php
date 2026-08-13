<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'active',
                'created_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);
            }

            return $order;
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update($data);

            if (isset($data['items'])) {
                $order->items()->delete();

                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }
            }

            return $order;
        });
    }

    public function complete(Order $order): Order|JsonResponse
    {
        if ($order->status !== 'active') {
            return response()->json(['message' => 'Only active orders can be completed.'], 422);
        }

        return DB::transaction(function () use ($order) {
            $order->load('items');

            foreach ($order->items as $item) {
                Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->decrement('stock', $item->count);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'doc_type' => 'Order',
                    'doc_id' => $order->id,
                    'quantity' => -$item->count,
                    'created_at' => now(),
                ]);
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $order;
        });
    }

    public function cancel(Order $order): Order|JsonResponse
    {
        if ($order->status !== 'active') {
            return response()->json(['message' => 'Only active orders can be canceled.'], 422);
        }

        $order->update(['status' => 'canceled']);

        return $order;
    }

    public function resume(Order $order): Order|JsonResponse
    {
        if ($order->status !== 'canceled') {
            return response()->json(['message' => 'Only canceled orders can be resumed.'], 422);
        }

        $order->load('items');

        $unavailableProducts = [];

        foreach ($order->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            if (!$stock || $stock->stock < $item->count) {
                $unavailableProducts[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'required' => $item->count,
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

        $order->update(['status' => 'active']);

        return $order;
    }
}
