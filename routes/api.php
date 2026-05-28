<?php

use App\Http\Controllers\Api\Finance\AuthController;
use App\Http\Controllers\Api\Finance\DashboardAccessController;
use App\Http\Controllers\Api\Finance\HauptkasseController;
use App\Http\Controllers\Api\Finance\StationController as FinanceStationController;
use App\Http\Controllers\Api\Finance\TeamPhotoController;
use App\Http\Controllers\Api\Finance\TicketController as FinanceTicketController;
use App\Http\Controllers\Api\Finance\UserController;
use App\Http\Controllers\Api\Public\MemberController;
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

Route::get('/members/{token}', [MemberController::class, 'show']);
Route::get('/members/{token}/avatar', [MemberController::class, 'avatar']);

Route::middleware('auth:sanctum')->prefix('finance')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/stations/cash-balances', [FinanceStationController::class, 'cashBalances']);
    Route::get('/stations/{station}/sign', [FinanceStationController::class, 'sign']);
    Route::apiResource('stations', FinanceStationController::class);

    Route::get('/tickets', [FinanceTicketController::class, 'index']);
    Route::post('/tickets', [FinanceTicketController::class, 'store']);
    Route::patch('/tickets/{ticket}/accept', [FinanceTicketController::class, 'accept']);
    Route::patch('/tickets/{ticket}/complete', [FinanceTicketController::class, 'complete']);
    Route::post('/tickets/{ticket}/print', [FinanceTicketController::class, 'print']);

    Route::get('/hauptkasse/pretix-bookings', [HauptkasseController::class, 'pretixBookings']);
    Route::get('/hauptkasse/kassenbuch', [HauptkasseController::class, 'kassenbuchEintraege']);
    Route::post('/hauptkasse/kassenbuch', [HauptkasseController::class, 'addKassenbuchEintrag']);
    Route::post('/hauptkasse/kassenbuch/from-pretix', [HauptkasseController::class, 'pushPretixBooking']);

    Route::get('/dashboard-access', [DashboardAccessController::class, 'index']);
    Route::post('/dashboard-access', [DashboardAccessController::class, 'store']);
    Route::delete('/dashboard-access/{dashboardAccess}', [DashboardAccessController::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);
    Route::match(['patch', 'post'], '/profile', [UserController::class, 'updateProfile']);
    Route::post('/password', [UserController::class, 'changePassword']);

    Route::get('/team-photo', [TeamPhotoController::class, 'show']);
    Route::get('/team-photo/image', [TeamPhotoController::class, 'image']);
    Route::post('/team-photo', [TeamPhotoController::class, 'store']);
    Route::delete('/team-photo', [TeamPhotoController::class, 'destroy']);

});
