<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockMovementController extends Controller
{
    #[OA\Get(
        path: '/stock-movements',
        summary: 'Движения остатков',
        description: 'Возвращает историю движений остатков с пагинацией и фильтрацией по складу, товару, типу документа и дате.',
        tags: ['Stock Movements'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', description: 'Фильтр по складу', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product_id', in: 'query', description: 'Фильтр по товару', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'doc_type', in: 'query', description: 'Тип документа (полное имя класса)', schema: new OA\Schema(type: 'string', enum: ['App\\Models\\Order', 'App\\Models\\Supply', 'App\\Models\\Transfer'])),
            new OA\Parameter(name: 'created_from', in: 'query', description: 'Дата от (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', description: 'Дата до (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице (по умолчанию 15)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пагинированный список движений',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/stock-movements/filters',
        summary: 'Варианты фильтров',
        description: 'Возвращает списки складов, товаров и типов документов, по которым есть движения. Используется для заполнения фильтров на фронтенде.',
        tags: ['Stock Movements'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Данные для фильтров',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'warehouses', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                        new OA\Property(property: 'products', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        )),
                        new OA\Property(property: 'doc_types', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'value', type: 'string', example: 'App\\Models\\Order'),
                                new OA\Property(property: 'label', type: 'string', example: 'Заказ'),
                            ]
                        )),
                    ]
                )
            ),
        ]
    )]
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
