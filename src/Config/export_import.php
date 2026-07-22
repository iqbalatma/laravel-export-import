<?php

use Iqbalatma\LaravelExportImport\Models\Export;
use Iqbalatma\LaravelExportImport\Models\Import;
use Iqbalatma\LaravelExportImport\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the models that will be used by the package.
    | This allows you to override the default models with your own
    | implementations if necessary.
    |
    */

    "models" => [
        "user" => User::class,
        "import" => Import::class,
        "export" => Export::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    |
    | Define the directories used for storing export and import files.
    | Temporary files will be generated during processing before being
    | moved to the final disk storage.
    |
    */

    "path" => [
        "export_path" => "exports",
        "import_path" => "imports",
        "temporary" => "tmp",
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Disk
    |--------------------------------------------------------------------------
    |
    | The disk that will be used to store uploaded import files.
    | This should correspond to one of the disks defined in the
    | "filesystems" configuration file.
    |
    */

    "import_disk" => env("EXPORT_IMPORT_IMPORT_DISK", "s3"),

    /*
    |--------------------------------------------------------------------------
    | Temporary Disk
    |--------------------------------------------------------------------------
    |
    | The disk used to stage files while they are being processed. Rows are
    | read from this disk via native fopen/fgetcsv, so it MUST use the
    | "local" driver. Do not point this to a remote disk (e.g. s3) — the
    | file has to exist on the local filesystem to be read.
    |
    */

    "temporary_disk" => env("EXPORT_IMPORT_TEMPORARY_DISK", "local"),

    /*
    |--------------------------------------------------------------------------
    | Export Disk
    |--------------------------------------------------------------------------
    |
    | The disk where generated export files will be stored.
    | You may configure this to use local, s3, or any supported
    | filesystem disk.
    |
    */

    "export_disk" => env("EXPORT_IMPORT_EXPORT_DISK", "s3"),

    /*
    |--------------------------------------------------------------------------
    | Export Availability (Hours)
    |--------------------------------------------------------------------------
    |
    | Determines how long exported files remain available for download
    | before they expire. The value is defined in hours.
    |
    */

    "export_available_until" => 72,


    /*
    |--------------------------------------------------------------------------
    | Job Default Timeout (Seconds)
    |--------------------------------------------------------------------------
    |
    | Determines how long job timeout. This value is defined in seconds
    |
    */

    "job_timeout" => 1200,

];
