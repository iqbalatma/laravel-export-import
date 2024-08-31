<?php

namespace Iqbalatma\LaravelExportImport\Traits;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Iqbalatma\LaravelExportImport\Models\Import;

trait InteractWithImport
{
    protected UploadedFile $file;
    /** @var $import Import */
    protected $import;

    protected function createImportEntity(
        UploadedFile $file,
        string       $importType,
        string       $importName = null,
        string       $permissionName = null,
        string       $importPath = "",
        Closure      $callback = null
    ): self
    {
        if (is_null($importName)) {
            $importName = $importType;
        }
        DB::transaction(function () use ($file, $importType, $importName, $permissionName, $importPath, $callback) {
            $path = rtrim(implode(DIRECTORY_SEPARATOR, ["imports", Str::slug($importType), $importPath]), "/");

            $filename = Str::uuid() . ".csv";
            $fullPath = "$path/$filename";
            Storage::disk("s3")->putFileAs($path, $file, $filename);

            $this->import = config("export_import.models.import")::query()->create([
                "type" => $importType,
                "name" => $importName,
                "success_row" => null,
                "failed_row" => null,
                "total_row" => null,
                "path" => $path,
                "filename" => $filename,
                "original_filename" => $file->getClientOriginalName(),
                "full_path" => $fullPath,
                "failed_path" => null,
                "failed_filename" => null,
                "failed_full_path" => null,
                "permission_name" => $permissionName,
                "imported_by_id" => Auth::id(),
                "is_completed" => false,
                "imported_at" => null,
            ]);

            if (is_callable($callback)) {
                $callback();
            }
        });

        return $this;
    }
}
