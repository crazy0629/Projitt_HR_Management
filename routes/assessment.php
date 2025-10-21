<?php

use App\Http\Controllers\Assessment\AssessmentController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function() {

    Route::post('add', [AssessmentController::class, 'add']);
    Route::post('edit', [AssessmentController::class, 'edit']);
    Route::delete('delete', [AssessmentController::class, 'delete']);
    Route::get('single/{id}', [AssessmentController::class, 'single']);
    Route::get('list-with-filters', [AssessmentController::class, 'listAllWithFilters']);
    Route::post('change-status', [AssessmentController::class, 'changeStatus']);
    
});
