<?php

namespace Iqbalatma\LaravelExportImport\Interfaces;

interface PathGenerator
{
    public static function getExportPath(): string;
    public static function getImportPath(): string;
    public static function getTemporaryPath(): string;
}
