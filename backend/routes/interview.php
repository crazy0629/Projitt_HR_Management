<?php

use App\Http\Controllers\Interview\InterviewController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('add', [InterviewController::class, 'add']);
    Route::get('list-with-filters', [InterviewController::class, 'listAllWithFilters']);

});
