<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use App\Services\SupplyService;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    public function __construct(
        private SupplyService $supplyService,
    ) {}

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
        $supply = $this->supplyService->create($request->validated());

        return new SupplyResource($supply->load(['warehouse', 'items.product']));
    }
}
