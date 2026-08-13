<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function index()
    {
        return WarehouseResource::collection(Warehouse::all());
    }

    public function products(Warehouse $warehouse): JsonResponse
    {
        $products = Stock::where('warehouse_id', $warehouse->id)
            ->where('stock', '>', 0)
            ->with('product')
            ->get()
            ->map(fn ($stock) => [
                'id' => $stock->product->id,
                'name' => $stock->product->name,
                'price' => $stock->product->price,
                'stock' => $stock->stock,
            ]);

        return response()->json(['data' => $products]);
    }
}
