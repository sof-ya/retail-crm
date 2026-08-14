<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use App\Services\SupplyService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SupplyController extends Controller
{
    public function __construct(
        private SupplyService $supplyService,
    ) {}

    /**
     * Список поставок с фильтрацией по складу и дате.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\SupplyResource>
     */
    #[OA\Get(
        path: '/supplies',
        summary: 'Список поставок',
        description: 'Возвращает поставки с пагинацией и фильтрацией по складу и дате.',
        tags: ['Supplies'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', description: 'Фильтр по складу', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'created_from', in: 'query', description: 'Дата от (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', description: 'Дата до (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице (по умолчанию 15)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пагинированный список поставок',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Supply')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
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

    /**
     * Создать поставку на склад.
     *
     * Увеличивает остатки товаров и записывает движения остатков.
     *
     * @param  \App\Http\Requests\StoreSupplyRequest  $request
     * @return \App\Http\Resources\SupplyResource
     */
    #[OA\Post(
        path: '/supplies',
        summary: 'Создать поставку',
        description: 'Создаёт поставку на склад. Увеличивает остатки товаров и записывает движения.',
        tags: ['Supplies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreSupplyRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Поставка создана',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Supply'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Ошибка валидации', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreSupplyRequest $request)
    {
        $supply = $this->supplyService->create($request->validated());

        return new SupplyResource($supply->load(['warehouse', 'items.product']));
    }
}
