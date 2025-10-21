<?php

use App\Http\Controllers\Interview\InterviewController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('add', [InterviewController::class, 'add']);
    Route::post('edit', [InterviewController::class, 'edit']);
    Route::post('change-status', [InterviewController::class, 'changeStatus']);
    Route::get('list-with-filters', [InterviewController::class, 'listAllWithFilters']);

});
