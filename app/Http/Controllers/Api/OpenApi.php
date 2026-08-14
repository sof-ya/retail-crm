<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Retail CRM API',
    description: 'API для управления розничной CRM-системой: товары, склады, заказы, поставки, перемещения, клиенты.',
)]
#[OA\Server(
    url: '/api',
    description: 'API base path'
)]

// ── Schemas ──────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'Customer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+7 999 123-45-67'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'ivan@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'Warehouse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Основной склад'),
    ]
)]
#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Футболка белая'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1500.00),
        new OA\Property(property: 'stocks', type: 'array', items: new OA\Items(ref: '#/components/schemas/Stock')),
    ]
)]
#[OA\Schema(
    schema: 'Stock',
    properties: [
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_name', type: 'string', example: 'Основной склад'),
        new OA\Property(property: 'stock', type: 'integer', example: 42),
    ]
)]
#[OA\Schema(
    schema: 'OrderItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_name', type: 'string', example: 'Футболка белая'),
        new OA\Property(property: 'count', type: 'integer', example: 3),
    ]
)]
#[OA\Schema(
    schema: 'Order',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', example: 1),
        new OA\Property(property: 'customer_name', type: 'string', example: 'Иван Иванов'),
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_name', type: 'string', example: 'Основной склад'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'canceled'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'completed_at', type: 'string', format: 'datetime', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
    ]
)]
#[OA\Schema(
    schema: 'SupplyItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_name', type: 'string', example: 'Футболка белая'),
        new OA\Property(property: 'count', type: 'integer', example: 100),
    ]
)]
#[OA\Schema(
    schema: 'Supply',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_name', type: 'string', example: 'Основной склад'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/SupplyItem')),
    ]
)]
#[OA\Schema(
    schema: 'TransferItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_name', type: 'string', example: 'Футболка белая'),
        new OA\Property(property: 'count', type: 'integer', example: 20),
    ]
)]
#[OA\Schema(
    schema: 'Transfer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'from_warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'from_warehouse_name', type: 'string', example: 'Основной склад'),
        new OA\Property(property: 'to_warehouse_id', type: 'integer', example: 2),
        new OA\Property(property: 'to_warehouse_name', type: 'string', example: 'Филиал №2'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TransferItem')),
    ]
)]
#[OA\Schema(
    schema: 'StockMovement',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_name', type: 'string', example: 'Футболка белая'),
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_name', type: 'string', example: 'Основной склад'),
        new OA\Property(property: 'doc_type', type: 'string', example: 'App\\Models\\Order'),
        new OA\Property(property: 'doc_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: -5),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
    ]
)]

// ── Request Schemas ──────────────────────────────────────────────

#[OA\Schema(
    schema: 'StoreCustomerRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+7 999 123-45-67'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'ivan@example.com'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateCustomerRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Иван Петров'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StoreOrderItem',
    required: ['product_id', 'count'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'count', type: 'integer', minimum: 1, example: 3),
    ]
)]
#[OA\Schema(
    schema: 'StoreOrderRequest',
    required: ['customer_id', 'warehouse_id', 'items'],
    properties: [
        new OA\Property(property: 'customer_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/StoreOrderItem')),
    ]
)]
#[OA\Schema(
    schema: 'UpdateOrderRequest',
    properties: [
        new OA\Property(property: 'customer_id', type: 'integer'),
        new OA\Property(property: 'warehouse_id', type: 'integer'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/StoreOrderItem')),
    ]
)]
#[OA\Schema(
    schema: 'StoreSupplyItem',
    required: ['product_id', 'count'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'count', type: 'integer', minimum: 1, example: 100),
    ]
)]
#[OA\Schema(
    schema: 'StoreSupplyRequest',
    required: ['warehouse_id', 'items'],
    properties: [
        new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/StoreSupplyItem')),
    ]
)]
#[OA\Schema(
    schema: 'StoreTransferItem',
    required: ['product_id', 'count'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 5),
        new OA\Property(property: 'count', type: 'integer', minimum: 1, example: 20),
    ]
)]
#[OA\Schema(
    schema: 'StoreTransferRequest',
    required: ['from_warehouse_id', 'to_warehouse_id', 'items'],
    properties: [
        new OA\Property(property: 'from_warehouse_id', type: 'integer', example: 1),
        new OA\Property(property: 'to_warehouse_id', type: 'integer', example: 2),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/StoreTransferItem')),
    ]
)]

// ── Shared responses ─────────────────────────────────────────────

#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'UnavailableProducts',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Some products are not available in sufficient quantity.'),
        new OA\Property(
            property: 'unavailable_products',
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'product_id', type: 'integer'),
                new OA\Property(property: 'required', type: 'integer'),
                new OA\Property(property: 'available', type: 'integer'),
            ])
        ),
    ]
)]
class OpenApi {}
