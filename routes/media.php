<?php

use App\Http\Controllers\Media\MediaController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'either.auth.or.onboarded'], function() {
     
     Route::delete('delete', [MediaController::class, 'delete']);
    
});

Route::post('add', [MediaController::class, 'add']);
Route::get('single/{id}', [MediaController::class, 'single']);
Route::get('all', [MediaController::class, 'listAllWithFilters']);
