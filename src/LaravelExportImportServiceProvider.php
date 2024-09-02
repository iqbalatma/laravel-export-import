<?php

namespace Iqbalatma\LaravelExportImport;

use Illuminate\Support\ServiceProvider;

class LaravelExportImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/export_import.php', 'export_import');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__.'/Migrations' => database_path('migrations'),
        ]);

        $this->publishes([
            __DIR__.'/Config/export_import.php' => config_path('export_import.php'),
        ], "config");
    }
}
