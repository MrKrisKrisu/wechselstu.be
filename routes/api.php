<?php

use App\Http\Controllers\Api\Finance\AuthController;
use App\Http\Controllers\Api\Finance\DashboardAccessController;
use App\Http\Controllers\Api\Finance\StationController as FinanceStationController;
use App\Http\Controllers\Api\Finance\TicketController as FinanceTicketController;
use App\Http\Controllers\Api\Public\MonitorController;
use App\Http\Controllers\Api\Public\StationController as PublicStationController;
use App\Http\Controllers\Api\Public\TicketController as PublicTicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('stations/{token}')->group(function () {
    Route::get('/', [PublicStationController::class, 'show']);
    Route::post('/tickets', [PublicTicketController::class, 'store']);
});

Route::get('/tickets/{id}', [PublicTicketController::class, 'show']);

Route::get('/monitor', [MonitorController::class, 'index'])->middleware('dashboard.token');

Route::middleware('auth:sanctum')->prefix('finance')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('stations', FinanceStationController::class);

    Route::get('/tickets', [FinanceTicketController::class, 'index']);
    Route::patch('/tickets/{ticket}/accept', [FinanceTicketController::class, 'accept']);
    Route::patch('/tickets/{ticket}/complete', [FinanceTicketController::class, 'complete']);

    Route::get('/dashboard-access', [DashboardAccessController::class, 'index']);
    Route::post('/dashboard-access', [DashboardAccessController::class, 'store']);
    Route::delete('/dashboard-access/{dashboardAccess}', [DashboardAccessController::class, 'destroy']);
});
