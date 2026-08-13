<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

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
        $order = $this->orderService->create($request->validated());

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order = $this->orderService->update($order, $request->validated());

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    public function complete(Order $order)
    {
        $result = $this->orderService->complete($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }

    public function cancel(Order $order)
    {
        $result = $this->orderService->cancel($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }

    public function resume(Order $order)
    {
        $result = $this->orderService->resume($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }
}
