<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'doc']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('doc_type')) {
            $query->where('doc_type', $request->doc_type);
        }

        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        $perPage = $request->input('per_page', 15);

        return StockMovementResource::collection($query->paginate($perPage));
    }

    public function filters()
    {
        $warehouseIds = StockMovement::distinct()->pluck('warehouse_id');
        $productIds = StockMovement::distinct()->pluck('product_id');
        $docTypes = StockMovement::distinct()->pluck('doc_type');

        $warehouses = Warehouse::whereIn('id', $warehouseIds)->get(['id', 'name']);
        $products = Product::whereIn('id', $productIds)->get(['id', 'name']);

        $docTypeLabels = [
            'App\\Models\\Order' => 'Заказ',
            'App\\Models\\Supply' => 'Поставка',
            'App\\Models\\Transfer' => 'Перемещение',
        ];

        $docTypes = $docTypes->map(fn ($type) => [
            'value' => $type,
            'label' => $docTypeLabels[$type] ?? $type,
        ])->values();

        return response()->json([
            'warehouses' => $warehouses,
            'products' => $products,
            'doc_types' => $docTypes,
        ]);
    }
}
