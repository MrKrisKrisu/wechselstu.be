<?php

use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\PublicWorkOrderController;
use App\Http\Controllers\RegisterGroupController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Public Routes (No authentication, token via URL)
 */
Route::get('/cash-registers/{cashRegisterId}/status',
           [PublicWorkOrderController::class, 'status']
);
Route::post(
    '/cash-registers/{cashRegisterId}/work-orders',
    [PublicWorkOrderController::class, 'store']
);

Route::get('/register-groups/{group_id}', [RegisterGroupController::class, 'registers']);

/**
 * Admin Routes (Protected by auth:api)
 */
Route::middleware('auth:api')->group(function() {
    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::get('/work-orders/count', [WorkOrderController::class, 'count']);
    Route::get('/work-orders/{id}', [WorkOrderController::class, 'show']);
    Route::put('/work-orders/{id}', [WorkOrderController::class, 'update']);

    Route::get('/cash-registers', [CashRegisterController::class, 'index']);
    Route::post('/cash-registers', [CashRegisterController::class, 'store']);
    Route::put('/cash-registers/{id}', [CashRegisterController::class, 'update']);
    Route::post('/cash-registers/{id}/reset-token', [CashRegisterController::class, 'resetToken']);

    Route::get('/register-groups', [RegisterGroupController::class, 'index']);
});