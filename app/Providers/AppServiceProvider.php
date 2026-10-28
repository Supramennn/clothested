<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        // Non-functional requirement: HTTPS is mandatory on the deployed
        // app, so every generated URL is forced to the secure scheme.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
