<?php

namespace Iqbalatma\LaravelExportImport\Services;

class PathGenerator implements \Iqbalatma\LaravelExportImport\Interfaces\PathGenerator
{
    public static function getExportPath(): string
    {
        return "exports";
    }

    public static function getImportPath(): string
    {
        return "imports";
    }

    public static function getTemporaryPath(): string
    {
        return "";
    }
}
