<?php

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

Route::get('/', function() {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/register-group', function() {
    return Inertia::render('public/RegisterGroup');
})->name('register-group');

Route::get('/dashboard', function() {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/cash-registers/manage', function() {
    return Inertia::render('CashRegister');
})->middleware(['auth', 'verified']);


Route::get('/cash-registers/{cashRegister}/{token}', function(Request $request, string $cashRegister, string $token) {
    $model = CashRegister::findOrFail($cashRegister);

    if(!$token || $token !== $model->token) {
        throw new AccessDeniedHttpException('Invalid or missing token.');
    }

    return Inertia::render('public/CashRegisterLanding', [
        'cashRegister' => $model,
    ]);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
