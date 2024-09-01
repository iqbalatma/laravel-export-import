<?php


use Iqbalatma\LaravelExportImport\Models\Export;
use Iqbalatma\LaravelExportImport\Models\Import;
use Iqbalatma\LaravelExportImport\Models\User;

return [
    "models" => [
        "user" => User::class,
        "import" => Import::class,
        "export" => Export::class
    ],
    "path" => [
        "export_path" => "exports",
        "import_path" => "imports",
        "temporary" => "tmp"
    ],
    'export_available_until' => 72,
];
