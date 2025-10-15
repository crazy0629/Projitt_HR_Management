<?php

use App\Http\Controllers\Country\CountryController;
use App\Http\Controllers\Country\UsStateController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'either.auth.or.onboarded'], function () {

    Route::get('/', [CountryController::class, 'index']);
    // Route::get('/stats', [UsStateController::class, 'index']);

});
