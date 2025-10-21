<?php

use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;


Route::post('add', [TeamController::class, 'add']);
Route::post('edit', [TeamController::class, 'edit']);
Route::get('single/{id}', [TeamController::class, 'single']);
Route::delete('delete', [TeamController::class, 'delete']);
Route::post('merge', [TeamController::class, 'merge']);
Route::get('list-with-filters', [TeamController::class, 'listAllWithFilters']);

     
