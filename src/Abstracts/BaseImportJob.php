<?php

namespace Iqbalatma\LaravelExportImport\Abstracts;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Iqbalatma\LaravelExportImport\ImportStatus;
use RuntimeException;

abstract class BaseImportJob
{
    public string|null $status = null;
    public int $timeout = 1200;
    protected int $successRow, $failedRow, $totalRow;
    /** @var resource|null */
    protected $file, $errorFile;
    protected Model $user;

    protected bool $isFirstRowSkipped;
    protected bool $isFileErrorExists;

    public function __construct(protected $import)
    {
        $this->totalRow = $this->failedRow = $this->successRow = 0;
        $this->isFirstRowSkipped = false;
        $this->isFileErrorExists = false;
        $this->user = $this->import->imported_by;
    }

    public function handle(): void
    {
        try {
            $this->setFile()
                ->readFile()
                ->importComplete();
        } catch (Exception $e) {
            $this->importFailed(get_class($e) . " : " . $e->getMessage());
            throw new RuntimeException($e);
        }
    }

    /**
     * @return void
     */
    private function uploadFileErrorToDisk(): void
    {
        if ($this->isFileErrorExists) {
            Storage::disk(config("export_import.import_disk"))->putFileAs(
                $this->import->failed_path,
                storage_path("app/" . config("export_import.path.temporary") . "/errors/{$this->import->failed_filename}"),
                $this->import->failed_filename
            );
        }
    }

    /**
     * @return void
     */
    private function deleteTmpFile(): void
    {
        Storage::delete(config("export_import.path.temporary") . "/{$this->import->filename}");

        if ($this->isFileErrorExists) {
            Storage::delete("/" . config("export_import.path.temporary") . "/errors/{$this->import->failed_filename}");
        }
    }


    /**
     * @return $this
     * @throws Exception
     */
    protected function setFile(): self
    {
        if (Storage::disk(config("export_import.import_disk"))->exists($this->import->full_path)) {
            $file = Storage::disk(config("export_import.import_disk"))->get($this->import->full_path);

            Storage::put(config("export_import.path.temporary") . "/{$this->import->filename}", $file);
            $this->file = fopen(storage_path("app/" . config("export_import.path.temporary") . "/{$this->import->filename}"), mode: "r");
        } else {
            throw new RuntimeException("File not found");
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function generateFileError(): self
    {
        if (!$this->isFileErrorExists) {
            File::ensureDirectoryExists(storage_path("app/" . config("export_import.path.temporary") . "/errors"));
            $this->errorFile = fopen(storage_path("app/" . config("export_import.path.temporary") . "/errors/error-{$this->import->filename}"), mode: "w");
            $this->isFileErrorExists = true;
        }
        return $this;
    }


    /**
     * @param array $errorRow
     * @param string $errorMessage
     * @return $this
     */
    protected function writeErrorRow(array $errorRow, string $errorMessage): self
    {
        fputcsv($this->errorFile, array_merge($errorRow, [$errorMessage]));
        return $this;
    }


    /**
     * @return $this
     */
    protected function importComplete(): self
    {
        $this->import->is_completed = true;
        $this->import->total_row = $this->totalRow;
        $this->import->success_row = $this->successRow;
        $this->import->failed_row = $this->failedRow;
        $this->import->imported_at = Carbon::now();
        $this->import->status = $this->status ?: ImportStatus::COMPLETED->name;

        fclose($this->file);
        if ($this->isFileErrorExists) {
            fclose($this->errorFile);
            $this->import->failed_path = $this->import->path . "/errors";
            $this->import->failed_filename = "error-" . $this->import->filename;
            $this->import->failed_full_path = $this->import->failed_path . "/" . $this->import->failed_filename;
        }
        $this->import->save();

        $this->uploadFileErrorToDisk();
        $this->deleteTmpFile();
        return $this;
    }

    /**
     * @return $this
     */
    protected function importPartiallyCompleted(): self
    {
        if ($this->successRow > 0) {
            $this->status = ImportStatus::PARTIAL_COMPLETED->name;
        } else {
            $this->status = ImportStatus::FAILED->name;
        }

        return $this;
    }

    /**
     * @param string|null $message
     * @return $this
     */
    protected function importFailed(string|null $message = null): self
    {
        $this->import->status = ImportStatus::FAILED->name;
        $this->import->failed_message = $message;
        $this->import->save();
        return $this;
    }

    /**
     * @param string $separator
     * @param bool $isSkipHeader
     * @return LazyCollection
     */
    protected function getLazyCollection(string $separator = ",", bool $isSkipHeader = true): LazyCollection
    {
        return LazyCollection::make(function () use ($separator, $isSkipHeader) {
            while (($row = fgetcsv($this->file, separator: $separator)) !== false) {
                if (!$this->isFirstRowSkipped && $isSkipHeader) {
                    $this->isFirstRowSkipped = true;
                    continue;
                }

                yield $row;
            }
        });
    }
}
