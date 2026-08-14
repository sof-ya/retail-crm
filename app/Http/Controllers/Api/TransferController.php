<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransferController extends Controller
{
    public function __construct(
        private TransferService $transferService,
    ) {}

    /**
     * Список перемещений с фильтрацией по складам-отправителю/получателю и дате.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\TransferResource>
     */
    #[OA\Get(
        path: '/transfers',
        summary: 'Список перемещений',
        description: 'Возвращает перемещения с пагинацией и фильтрацией по складам-отправителю/получателю и дате.',
        tags: ['Transfers'],
        parameters: [
            new OA\Parameter(name: 'from_warehouse_id', in: 'query', description: 'Фильтр по складу-отправителю', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'to_warehouse_id', in: 'query', description: 'Фильтр по складу-получателю', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'created_from', in: 'query', description: 'Дата от (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', description: 'Дата до (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице (по умолчанию 15)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пагинированный список перемещений',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Transfer')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'items.product']);

        // если переданы параметры фильтров, то фильтруем по ним запрос 
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

    /**
     * Создать перемещение товаров между складами.
     *
     * Проверяет наличие на складе-отправителе. Создаёт два движения остатков (списание и приход).
     *
     * @param  \App\Http\Requests\StoreTransferRequest  $request
     * @return \App\Http\Resources\TransferResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/transfers',
        summary: 'Создать перемещение',
        description: 'Перемещает товары между складами. Проверяет наличие на складе-отправителе. Создаёт два движения остатков (списание и приход).',
        tags: ['Transfers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreTransferRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Перемещение создано',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Transfer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Ошибка валидации / недостаточно товара на складе-отправителе', content: new OA\JsonContent(ref: '#/components/schemas/UnavailableProducts')),
        ]
    )]
    public function store(StoreTransferRequest $request)
    {
        $result = $this->transferService->create($request->validated());

        // возвращается либо объект перемещения, либо ошибка
        if ($result instanceof Transfer) {
            return new TransferResource($result->load(['fromWarehouse', 'toWarehouse', 'items.product']));
        }

        return $result;
    }
}
