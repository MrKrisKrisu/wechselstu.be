<?php

use App\Http\Middleware\DetectDomainTicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/s/{token}', function (Request $request, string $token) {
    $ticketType = $request->attributes->get('ticket_type');

    if ($ticketType && ! $request->query('type')) {
        return redirect('/s/'.$token.'?type='.$ticketType);
    }

    return view('app');
})->middleware(DetectDomainTicketType::class);

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
