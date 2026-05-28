<?php

use App\Enums\TicketType;
use Illuminate\Support\Facades\Route;

// Ticket-type domains: /s/{token} gets an automatic ?type= redirect
foreach ([
    TicketType::CashFull->value => config('domains.cash_full'),
    TicketType::ChangeRequest->value => config('domains.change_request'),
    TicketType::Other->value => config('domains.other'),
] as $typeValue => $domain) {
    if (! $domain) {
        continue;
    }

    Route::domain($domain)->get('/s/{token}', function (string $token) use ($typeValue) {
        return request()->query('type')
            ? view('app')
            : redirect('/s/'.$token.'?type='.$typeValue);
    });
}

// Member domain: only serves profile pages, everything else is 404
Route::domain(config('domains.member', 'member.localhost'))->group(function () {
    Route::get('/{token}', function (string $token) {
        return redirect('/member/'.$token);
    })->where('token', '[A-Za-z0-9]{16,}');

    Route::get('/member/{token}', function () {
        return view('app');
    });

    Route::get('/{any}', function () {
        abort(404);
    })->where('any', '(?!api(?:/|$)).*');
});

// SPA catchall for all other domains
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
