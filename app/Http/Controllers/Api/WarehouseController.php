<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class WarehouseController extends Controller
{
    /**
     * Список всех складов.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\WarehouseResource>
     */
    #[OA\Get(
        path: '/warehouses',
        summary: 'Список складов',
        description: 'Возвращает все склады. Без пагинации.',
        tags: ['Warehouses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список складов',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Warehouse')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        return WarehouseResource::collection(Warehouse::all());
    }

    /**
     * Товары с положительным остатком на указанном складе.
     *
     * @param  \App\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/warehouses/{id}/products',
        summary: 'Товары на складе',
        description: 'Возвращает товары с положительным остатком на указанном складе.',
        tags: ['Warehouses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список товаров с остатком',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'price', type: 'number', format: 'float'),
                                new OA\Property(property: 'stock', type: 'integer'),
                            ])
                        ),
                    ]
                )
            ),
        ]
    )]
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
