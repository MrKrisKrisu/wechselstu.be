<?php

use App\Http\Controllers\PublicWorkOrderController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Public Routes (No authentication, token via URL)
 */
Route::post(
    '/registers/{token}/work-orders',
    [PublicWorkOrderController::class, 'store']
);

/**
 * Admin Routes (Protected by auth:api)
 */
Route::middleware('auth:api')->group(function() {
    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::get('/work-orders/count', [WorkOrderController::class, 'count']);
    Route::get('/work-orders/{id}', [WorkOrderController::class, 'show']);
    Route::put('/work-orders/{id}', [WorkOrderController::class, 'update']);
});