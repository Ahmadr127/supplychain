<?php

namespace App\Providers;

use App\Services\Import\ColumnMapper;
use App\Services\Import\DynamicImportService;
use App\Services\Import\HeaderDetector;
use App\Services\Import\ResultBuilder;
use App\Services\Import\ValidationEngine;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImportEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/import-engine.php'),
            'import-engine'
        );

        // Register services as singletons
        $this->app->singleton(HeaderDetector::class);
        $this->app->singleton(ColumnMapper::class);
        $this->app->singleton(ValidationEngine::class);
        $this->app->singleton(ResultBuilder::class);

        $this->app->singleton(DynamicImportService::class, function ($app) {
            return new DynamicImportService(
                $app->make(ColumnMapper::class),
                $app->make(ValidationEngine::class),
                $app->make(ResultBuilder::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
