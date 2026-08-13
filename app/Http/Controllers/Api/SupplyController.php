<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Stock;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $query = Supply::with(['warehouse', 'items.product']);

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

        return SupplyResource::collection($query->paginate($perPage));
    }

    public function store(StoreSupplyRequest $request)
    {
        $supply = DB::transaction(function () use ($request) {
            $supply = Supply::create([
                'warehouse_id' => $request->warehouse_id,
                'created_at' => now(),
            ]);

            foreach ($request->items as $item) {
                $supply->items()->create([
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);

                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $request->warehouse_id);

                if ($stock->exists()) {
                    $stock->increment('stock', $item['count']);
                } else {
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'stock' => $item['count'],
                    ]);
                }
            }

            return $supply;
        });

        return new SupplyResource($supply->load(['warehouse', 'items.product']));
    }
}
