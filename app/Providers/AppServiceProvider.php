<?php

namespace App\Providers;

use App\Support\Vite;
use Illuminate\Foundation\Vite as BaseVite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BaseVite::class, Vite::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()?->isSecure() || request()?->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
