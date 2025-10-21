<?php

use App\Http\Controllers\CookiesTrackingController;
use App\Http\Controllers\CookiesVisitController;
use App\Http\Controllers\Demo\DemoController;
use App\Http\Controllers\Job\WebJobController;
use App\Http\Controllers\PriceQuoteController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\Route;


Route::post('demo/add', [DemoController::class, 'add']);
Route::post('price-quote/add', [PriceQuoteController::class, 'add']);
Route::post('job/add', [WebJobController::class, 'add']);

Route::post('wait-list/add', [WaitListController::class, 'add']);
Route::post('cookies/add', [CookiesVisitController::class, 'add']);
Route::post('cookies-tracking/add', [CookiesTrackingController::class, 'add']);
Route::post('cookies-tracking/add', [CookiesTrackingController::class, 'add']);

Route::post('support/add', [SupportController::class, 'add']);

Route::get('wait-list', [WaitListController::class, 'listAllWithFilters']);