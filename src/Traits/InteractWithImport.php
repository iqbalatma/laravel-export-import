<?php

namespace Iqbalatma\LaravelExportImport\Traits;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Iqbalatma\LaravelExportImport\ImportStatus;
use Iqbalatma\LaravelExportImport\Models\Import;
use Throwable;

trait InteractWithImport
{
    protected UploadedFile $file;
    /** @var $import Import */
    protected Import $import;

    /**
     * @param UploadedFile $file
     * @param string $importType
     * @param string|null $importName
     * @param string|null $permissionName
     * @param string $importPath
     * @param Closure|null $callback
     * @return InteractWithImport
     * @throws Throwable
     */
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
            Storage::disk(config("export_import.import_disk"))->putFileAs($path, $file, $filename);

            $this->import = config("export_import.models.import")::query()->create([
                "type" => $importType,
                "name" => $importName,
                "success_row" => null,
                "failed_row" => null,
                "total_row" => null,
                "path" => $path,
                "filename" => $filename,
                "status" => ImportStatus::ON_PROGRESS->name,
                "failed_message" => null,
                "original_filename" => $file->getClientOriginalName(),
                "full_path" => "$path/$filename",
                "failed_path" => null,
                "failed_filename" => null,
                "failed_full_path" => null,
                "permission_name" => $permissionName,
                "imported_by_id" => Auth::id(),
                "is_completed" => false,
                "imported_at" => null,
            ]);
        });
        if (is_callable($callback)) {
            $callback();
        }

        return $this;
    }
}
