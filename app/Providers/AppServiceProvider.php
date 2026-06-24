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
        // Register Firebase service as singleton
        $this->app->singleton(\App\Services\FirebaseService::class, function ($app) {
            return new \App\Services\FirebaseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom middleware
        $this->app['router']->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);

        // Force HTTPS jika APP_URL menggunakan https (mengatasi insecure form submission & redirect 302 drop method)
        // Note: Tidak menggunakan env() langsung karena akan return null jika config di-cache.
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
