<?php

use Illuminate\Support\Facades\Route;

return function () {

    Route::prefix('user')->group(base_path('routes/user.php'));
    Route::prefix('master')->group(base_path('routes/master.php'));
    Route::prefix('media')->group(base_path('routes/media.php'));
    Route::prefix('country')->group(base_path('routes/country.php'));
    Route::prefix('question')->group(base_path('routes/question.php'));
    Route::prefix('job')->group(base_path('routes/job.php'));
    Route::prefix('assessment')->group(base_path('routes/assessment.php'));
    Route::prefix('interview')->group(base_path('routes/interview.php'));
    Route::prefix('web-public')->group(base_path('routes/web_public.php'));
    Route::prefix('employee')->group(base_path('routes/employee.php'));
    Route::prefix('team')->group(base_path('routes/team.php'));
    
};