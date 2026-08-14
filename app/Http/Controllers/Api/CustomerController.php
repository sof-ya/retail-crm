<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    #[OA\Get(
        path: '/customers',
        summary: 'Список клиентов',
        description: 'Возвращает клиентов с фильтрацией по имени, телефону, email. Если передан per_page — пагинация, иначе все записи.',
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'name', in: 'query', description: 'Поиск по имени (LIKE)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'phone', in: 'query', description: 'Поиск по телефону (LIKE)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'email', in: 'query', description: 'Поиск по email (LIKE)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Кол-во на странице. Без параметра — все записи.', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список клиентов',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Customer')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->has('per_page')) {
            return CustomerResource::collection($query->paginate($request->input('per_page', 15)));
        }

        return CustomerResource::collection($query->get());
    }

    #[OA\Post(
        path: '/customers',
        summary: 'Создать клиента',
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCustomerRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Клиент создан',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Customer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Ошибка валидации', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        $data['created_at'] = now();

        $customer = Customer::create($data);

        return new CustomerResource($customer);
    }

    #[OA\Put(
        path: '/customers/{id}',
        summary: 'Обновить клиента',
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateCustomerRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Клиент обновлён',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Customer'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Клиент не найден'),
            new OA\Response(response: 422, description: 'Ошибка валидации', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }
}
