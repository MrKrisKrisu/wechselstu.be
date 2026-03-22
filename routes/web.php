<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all: Vue Router handles all frontend routing
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
