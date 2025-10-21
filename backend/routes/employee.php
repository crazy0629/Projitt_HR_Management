<?php

// use App\Http\Controllers\User\UserController;

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;


// Route::post('register', [UserController::class, 'register']);
// Route::put('login', [UserController::class, 'login']);
// Route::get('role/list-with-filters', [UserController::class, 'listAllWithFiltersRole']);

// Route::post('forgot-password', [UserController::class, 'forgotPassword']);
// Route::get('password-reset/{token}', [UserController::class, 'validateResetPasswordToken']);
// Route::post('password-reset', [UserController::class, 'resetPassword']);
// Route::get('password-reset/{token}', [UserController::class, 'validateResetPasswordToken'])
//      ->name('password.reset.form');
// Route::post('password-update', [UserController::class, 'resetPassword'])
//      ->name('password.update');

// Route::group(['middleware' => 'auth:sanctum'], function() {

//     Route::get('logout', [UserController::class, 'logout']);
//     Route::post('/refresh-token', [UserController::class, 'refreshToken']);
//     Route::get('test', [UserController::class, 'test']);
    
// });



// // Route::get('test', [UserController::class, 'applicantsss']); 

// Route::middleware('applicant.onboarded')->group(function () {
//      Route::get('test', [UserController::class, 'applicantsss']);
//  });

Route::post('add', [EmployeeController::class, 'add']);
Route::post('update/step-2', [EmployeeController::class, 'updateStep2']);
Route::post('update/step-3', [EmployeeController::class, 'updateStep3']);
Route::post('update/step-4', [EmployeeController::class, 'updateStep4']);
Route::post('update-password', [EmployeeController::class, 'updatePassword']);
Route::post('login', [EmployeeController::class, 'login']);


     
