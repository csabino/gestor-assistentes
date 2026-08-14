<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Garante que o EasyPanel limpe caches de rotas antigos em builds
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            @unlink(base_path('bootstrap/cache/routes-v7.php'));
        }
    }
}