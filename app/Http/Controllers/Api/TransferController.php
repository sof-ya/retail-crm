<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private TransferService $transferService,
    ) {}

    public function index(Request $request)
    {
        $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'items.product']);

        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        $perPage = $request->input('per_page', 15);

        return TransferResource::collection($query->paginate($perPage));
    }

    public function store(StoreTransferRequest $request)
    {
        $result = $this->transferService->create($request->validated());

        if ($result instanceof Transfer) {
            return new TransferResource($result->load(['fromWarehouse', 'toWarehouse', 'items.product']));
        }

        return $result;
    }
}
