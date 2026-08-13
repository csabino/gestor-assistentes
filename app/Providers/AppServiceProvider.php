<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Força todas as URLs e formulários a usarem HTTPS em produção
        if (config('app.env') !== 'local' || str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}