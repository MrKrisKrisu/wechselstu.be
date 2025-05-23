<?php

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

Route::get('/', function() {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', function() {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/cash-registers/manage', function() {
    return Inertia::render('CashRegister');
})->middleware(['auth', 'verified']);


Route::get('/cash-registers/{cashRegister}', function(Request $request, string $cashRegister) {
    $model = CashRegister::findOrFail($cashRegister);

    $token = $request->query('token');
    if(!$token || $token !== $model->token) {
        throw new AccessDeniedHttpException('Ungültiger oder fehlender Token.');
    }

    return Inertia::render('public/CashRegisterLanding', [
        'cashRegister' => $model,
    ]);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
