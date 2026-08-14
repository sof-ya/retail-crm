<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    /**
     * Список товаров с остатками по складам.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\ProductResource>
     */
    #[OA\Get(
        path: '/products',
        summary: 'Список товаров',
        description: 'Возвращает товары с остатками по складам. Фильтрация по складу, пагинация по per_page.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', description: 'Фильтр по складу (товары с остатком > 0 на указанном складе)', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице. Без параметра — все записи.', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список товаров',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Product::with('stocks.warehouse');

        if ($request->filled('warehouse_id')) {
            $query->whereHas('stocks', fn ($q) => $q
                ->where('warehouse_id', $request->warehouse_id)
                ->where('stock', '>', 0)
            );
        }

        if ($request->has('per_page')) {
            return ProductResource::collection($query->paginate($request->input('per_page', 15)));
        }

        return ProductResource::collection($query->get());
    }
}
