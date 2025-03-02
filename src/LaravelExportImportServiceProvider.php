<?php

namespace Iqbalatma\LaravelExportImport;

use Illuminate\Support\ServiceProvider;
use Iqbalatma\LaravelExportImport\Exceptions\PathGeneratorException;
use Iqbalatma\LaravelExportImport\Interfaces\PathGenerator;

class LaravelExportImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/export_import.php', 'export_import');

        $this->publishesMigrations([
            __DIR__.'/Migrations' => database_path('migrations'),
        ], 'migration');

        $this->publishes([
            __DIR__.'/Config/export_import.php' => config_path('export_import.php'),
        ], "config");
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(PathGenerator::class, function (){
            $pathGenerator = config('export_import.path.path_generator');

            $pathGeneratorObject = new $pathGenerator;
            if (!($pathGeneratorObject instanceof PathGenerator)) {
                throw new PathGeneratorException("$pathGenerator must be implement " . PathGenerator::class);
            }

            return $pathGeneratorObject;
        });
    }
}
