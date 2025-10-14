<?php

use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'either.auth.or.onboarded'], function () {

     Route::post('add', [MasterController::class, 'add']);
     Route::post('edit', [MasterController::class, 'edit']);
     Route::delete('delete', [MasterController::class, 'delete']);
     Route::get('single/{id}', [MasterController::class, 'single']);
     Route::get('list-with-filters', [MasterController::class, 'listAllWithFilters']);
     Route::get('intellisense-search', [MasterController::class, 'intellisenseSearch']);

 });
