<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::controller(CurrencyController::class)->group(function () {
    Route::get('/exchange-rate', 'exchangeRate')->name('currency.exchange-rate');
    Route::post('/convert', 'convert')->name('currency.convert');
    Route::post('/convert/usd-to-clp', 'usdToClp')->name('currency.usd-to-clp');
});
