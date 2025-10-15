<?php

use App\Http\Controllers\Question\CodingQuestionController;
use App\Http\Controllers\Question\QuestionController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('add', [QuestionController::class, 'add']);
    Route::post('edit', [QuestionController::class, 'edit']);
    Route::delete('delete', [QuestionController::class, 'delete']);
    Route::get('single/{id}', [QuestionController::class, 'single']);
    Route::get('list-with-filters', [QuestionController::class, 'listAllWithFilters']);
    Route::get('intellisense-search', [QuestionController::class, 'intellisenseSearch']);

    Route::post('coding/add', [CodingQuestionController::class, 'add']);
    Route::post('coding/edit', [CodingQuestionController::class, 'edit']);
    Route::delete('coding/delete', [CodingQuestionController::class, 'delete']);
    Route::get('coding/single/{id}', [CodingQuestionController::class, 'single']);
    Route::get('coding/list-with-filters', [CodingQuestionController::class, 'listAllWithFilters']);

});
