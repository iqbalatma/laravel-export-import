<?php


use Iqbalatma\LaravelExportImport\Models\Export;
use Iqbalatma\LaravelExportImport\Models\Import;
use Iqbalatma\LaravelExportImport\Models\User;


return [
    "models" => [
        /*
        |--------------------------------------------------------------------------
        | Model User
        |--------------------------------------------------------------------------
        |
        | This value is the model of user for column exported by and imported by
        | that refer on Iqbalatma\LaravelExportImport\Models\Export
        | and Iqbalatma\LaravelExportImport\Models\import model. You can modify this
        | model to fulfill your requirement
        |
        */

        "user" => User::class,

        /*
        |--------------------------------------------------------------------------
        | Model Import
        |--------------------------------------------------------------------------
        |
        | This value is the model import that will hold data history of your import.
        | You can monitor is this import is complete, import by who, and how many success row
        |
        */
        "import" => Import::class,

        /*
        |--------------------------------------------------------------------------
        | Model Export
        |--------------------------------------------------------------------------
        |
        | This value is the model export that will hold data history of your export.
        | You can monitor who export some action and download exported file
        |
        */
        "export" => Export::class
    ],

    "path" => [
        "path_generator" => Iqbalatma\LaravelExportImport\Services\PathGenerator::class,
        /*
         * TODO use file path generator
        |--------------------------------------------------------------------------
        | Export Path
        |--------------------------------------------------------------------------
        |
        | This is path of target exported file. You can specify or rename this path
        |
        */

        "export_path" => "exports",

        /*
        |--------------------------------------------------------------------------
        | Import Path
        |--------------------------------------------------------------------------
        |
        | This is path of target imported file. You can specify or rename this path
        |
        */

        "import_path" => "imports",

        /*
        |--------------------------------------------------------------------------
        | Export Path
        |--------------------------------------------------------------------------
        |
        | Temporary path is application target path that use to hold temporary file
        | csv before publish to destination. This is use when your exported and
        | imported storage in external drive like S3. Because we cannot write stream
        | directly on S3, so we need to write on our application and then publish
        | to S3
        |
        */

        "temporary" => "tmp"
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Retention
    |--------------------------------------------------------------------------
    |
    | We can set export to available until some amount of time, to prevent our
    | storage full
    |
    */
    'export_available_until' => 72,
];
