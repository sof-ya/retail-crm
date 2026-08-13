<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'warehouse', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        $perPage = $request->input('per_page', 15);

        return OrderResource::collection($query->paginate($perPage));
    }

    public function store(StoreOrderRequest $request)
    {
        $order = DB::transaction(function () use ($request) {
            $order = Order::create([
                'customer_id' => $request->customer_id,
                'warehouse_id' => $request->warehouse_id,
                'status' => 'active',
                'created_at' => now(),
            ]);

            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);
            }

            return $order;
        });

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order = DB::transaction(function () use ($request, $order) {
            $order->update($request->validated());

            if ($request->has('items')) {
                $order->items()->delete();

                foreach ($request->items as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }
            }

            return $order;
        });

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function complete(Order $order)
    {
        if ($order->status !== 'active') {
            return response()->json(['message' => 'Only active orders can be completed.'], 422);
        }

        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function cancel(Order $order)
    {
        if ($order->status !== 'active') {
            return response()->json(['message' => 'Only active orders can be canceled.'], 422);
        }

        $order->update(['status' => 'canceled']);

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function resume(Order $order)
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

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }
}
