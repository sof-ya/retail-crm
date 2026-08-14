<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    /**
     * Список заказов с фильтрацией по статусу, клиенту, складу и дате создания.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    #[OA\Get(
        path: '/orders',
        summary: 'Список заказов',
        description: 'Возвращает заказы с пагинацией и фильтрацией по статусу, клиенту, складу, дате создания.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Фильтр по статусу', schema: new OA\Schema(type: 'string', enum: ['active', 'completed', 'canceled'])),
            new OA\Parameter(name: 'customer_id', in: 'query', description: 'Фильтр по клиенту', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_id', in: 'query', description: 'Фильтр по складу', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'created_from', in: 'query', description: 'Дата создания от (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', description: 'Дата создания до (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице (по умолчанию 15)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пагинированный список заказов',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'warehouse', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

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

        return OrderResource::collection($query->paginate($perPage));
    }

    /**
     * Детали заказа с позициями, клиентом и складом.
     *
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource
     */
    #[OA\Get(
        path: '/orders/{id}',
        summary: 'Детали заказа',
        description: 'Возвращает заказ с позициями, клиентом и складом.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заказ',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Заказ не найден'),
        ]
    )]
    public function show(Order $order)
    {
        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    /**
     * Создать заказ со списком позиций.
     *
     * Проверяет наличие товара на складе. При недостатке возвращает 422 с unavailable_products.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \App\Http\Resources\OrderResource
     */
    #[OA\Post(
        path: '/orders',
        summary: 'Создать заказ',
        description: 'Создаёт заказ со списком позиций. Проверяет наличие товара на складе. При недостатке возвращает 422 с unavailable_products.',
        tags: ['Orders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreOrderRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Заказ создан',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Ошибка валидации / недостаточно товара', content: new OA\JsonContent(ref: '#/components/schemas/UnavailableProducts')),
        ]
    )]
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->validated());

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    /**
     * Обновить заказ (только со статусом active).
     *
     * При обновлении позиций — пересчитывает движения остатков.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource
     */
    #[OA\Put(
        path: '/orders/{id}',
        summary: 'Обновить заказ',
        description: 'Обновляет заказ (только со статусом active). При обновлении позиций — пересчитывает движения остатков.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateOrderRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заказ обновлён',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Заказ не в статусе active'),
            new OA\Response(response: 422, description: 'Ошибка валидации', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order = $this->orderService->update($order, $request->validated());

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }

    /**
     * Завершить заказ (перевод в статус completed).
     *
     * Списание остатков и запись движений выполняются при завершении.
     *
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Patch(
        path: '/orders/{id}/complete',
        summary: 'Завершить заказ',
        description: 'Переводит заказ в статус completed. Списание остатков и запись движений выполняются при завершении.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заказ завершён',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Заказ не в статусе active', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Only active orders can be completed.'),
                ]
            )),
        ]
    )]
    public function complete(Order $order)
    {
        $result = $this->orderService->complete($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }

    /**
     * Отменить заказ (перевод в статус canceled).
     *
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Patch(
        path: '/orders/{id}/cancel',
        summary: 'Отменить заказ',
        description: 'Переводит заказ в статус canceled.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заказ отменён',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Заказ не в статусе active', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Only active orders can be canceled.'),
                ]
            )),
        ]
    )]
    public function cancel(Order $order)
    {
        $result = $this->orderService->cancel($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }

    /**
     * Возобновить отменённый заказ (перевод в статус active).
     *
     * Проверяет наличие товара на складе перед возобновлением.
     *
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Patch(
        path: '/orders/{id}/resume',
        summary: 'Возобновить заказ',
        description: 'Возвращает отменённый заказ в статус active. Проверяет наличие товара на складе.',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Заказ возобновлён',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Заказ не в статусе canceled / недостаточно товара', content: new OA\JsonContent(ref: '#/components/schemas/UnavailableProducts')),
        ]
    )]
    public function resume(Order $order)
    {
        $result = $this->orderService->resume($order);

        if ($result instanceof Order) {
            return new OrderResource($result->load(['customer', 'warehouse', 'items.product']));
        }

        return $result;
    }
}
