<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\VideoCall\Meeting;

class RouteBindingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::bind('meeting', function ($value) {
            return Meeting::findOrFail($value);
        });
    }

    public function register(): void
    {
        //
    }
}
