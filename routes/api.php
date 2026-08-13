<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplyController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::apiResource('warehouses', WarehouseController::class)->only(['index']);
Route::apiResource('products', ProductController::class)->only(['index']);
Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'update']);
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'update']);
Route::patch('orders/{order}/complete', [OrderController::class, 'complete']);
Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel']);
Route::patch('orders/{order}/resume', [OrderController::class, 'resume']);
Route::apiResource('supplies', SupplyController::class)->only(['index', 'store']);
