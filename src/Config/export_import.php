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
];
