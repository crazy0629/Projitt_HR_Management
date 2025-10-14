<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\User\UserAuthToken;
use App\Extensions\CustomSanctumGuard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {

        Sanctum::usePersonalAccessTokenModel(UserAuthToken::class);
        Sanctum::authenticateAccessTokensUsing(function ($token) {
            return (new CustomSanctumGuard)($token);
        });
        
    }
}
