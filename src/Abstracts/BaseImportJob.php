<?php

namespace Iqbalatma\LaravelExportImport\Abstracts;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Iqbalatma\LaravelExportImport\Exceptions\FailedToUploadFileToDiskException;
use Iqbalatma\LaravelExportImport\ImportStatus;
use Iqbalatma\LaravelExportImport\Models\Import;
use RuntimeException;

abstract class BaseImportJob
{
    public string|null $status = null;
    public int $timeout;
    protected int $successRow, $failedRow, $totalRow;
    /** @var resource|null */
    protected $file, $errorFile;
    protected Model $user;

    protected bool $isFirstRowSkipped;
    protected bool $isFileErrorExists;
    protected array $header;
    public string $temporaryPath;
    public string $temporaryDisk;


    public function __construct(protected Import $import)
    {
        $this->timeout = config("export_import.job_timeout");
        $this->temporaryPath = config("export_import.path.temporary");
        $this->temporaryDisk = config("export_import.temporary_disk");

        $this->totalRow = $this->failedRow = $this->successRow = 0;
        $this->isFirstRowSkipped = false;
        $this->isFileErrorExists = false;
        $this->user = $this->import->imported_by;
        $this->header = [];
    }

    public function handle(): void
    {
        try {
            $this->setFile()
                ->readFile()
                ->importComplete();

            $this->afterImport();
        } catch (Exception $e) {
            $this->importFailed(get_class($e) . " : " . $e->getMessage())
                ->afterImportFailed($e);
            throw new RuntimeException($e);
        } finally {
            if (is_resource($this->file)) {
                fclose($this->file);
            }

            if (is_resource($this->errorFile)) {
                fclose($this->errorFile);
            }
        }
    }

    /**
     * @return void
     * @throws FailedToUploadFileToDiskException
     */
    private function uploadFileErrorToDisk(): void
    {
        if ($this->isFileErrorExists) {
            $uploaded = Storage::disk(config("export_import.import_disk"))->putFileAs(
                $this->import->failed_path,
                Storage::disk($this->temporaryDisk)->path("$this->temporaryPath/errors/{$this->import->failed_filename}"),
                $this->import->failed_filename
            );

            if (!$uploaded) {
                throw new FailedToUploadFileToDiskException("Failed to upload {$this->import->failed_filename}");
            }
        }
    }

    /**
     * @return void
     */
    private function deleteTmpFile(): void
    {
        $disk = Storage::disk($this->temporaryDisk);
        $disk->delete("$this->temporaryPath/{$this->import->filename}");

        if ($this->isFileErrorExists) {
            $disk->delete("$this->temporaryPath/errors/{$this->import->failed_filename}");
        }
    }


    /**
     * @return $this
     * @throws Exception
     */
    protected function setFile(): self
    {
        $importDisk = config("export_import.import_disk");

        if (!Storage::disk($importDisk)->exists($this->import->full_path)) {
            throw new RuntimeException("File not found");
        }

        $temporaryFilePath = "$this->temporaryPath/{$this->import->filename}";

        Storage::disk($this->temporaryDisk)->put(
            $temporaryFilePath,
            Storage::disk($importDisk)->get($this->import->full_path)
        );
        $this->file = fopen(Storage::disk($this->temporaryDisk)->path($temporaryFilePath), mode: "r");

        return $this;
    }

    /**
     * @return $this
     */
    protected function generateFileError(): self
    {
        if (!$this->isFileErrorExists) {
            $disk = Storage::disk($this->temporaryDisk);
            File::ensureDirectoryExists($disk->path("$this->temporaryPath/errors"));
            $this->errorFile = fopen($disk->path("$this->temporaryPath/errors/error-{$this->import->filename}"), mode: "w");
            $this->isFileErrorExists = true;
            fputcsv($this->errorFile, array_merge($this->header, ["error_message"]));
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
     * @throws FailedToUploadFileToDiskException
     */
    protected function importComplete(): self
    {
        if ($this->failedRow === 0 && $this->successRow > 0) {
            $this->status = ImportStatus::COMPLETED->name;
        } elseif ($this->failedRow > 0 && $this->successRow > 0) {
            $this->status = ImportStatus::PARTIAL_COMPLETED->name;
        } elseif ($this->failedRow > 0 && $this->successRow === 0) {
            $this->status = ImportStatus::FAILED->name;
        }
        $this->import->is_completed = true;
        $this->import->total_row = $this->totalRow;
        $this->import->success_row = $this->successRow;
        $this->import->failed_row = $this->failedRow;
        $this->import->imported_at = Carbon::now();
        $this->import->status = $this->status ?: ImportStatus::COMPLETED->name;

        if ($this->isFileErrorExists) {
            $this->import->failed_path = "{$this->import->path}/errors";
            $this->import->failed_filename = "error-{$this->import->filename}";
            $this->import->failed_full_path = "{$this->import->failed_path}/{$this->import->failed_filename}";
        }
        $this->import->save();

        $this->uploadFileErrorToDisk();
        $this->deleteTmpFile();
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
                if (!$this->isFirstRowSkipped) {
                    $this->header = $row;
                    $this->isFirstRowSkipped = true;
                    if ($isSkipHeader) {
                        continue;
                    }
                }

                yield $row;
            }
        });
    }

    /**
     * @return self
     */
    abstract protected function readFile(): self;

    /**
     * @return void
     */
    protected function afterImport(): void
    {
    }


    /**
     * @param Exception $e
     * @return void
     */
    protected function afterImportFailed(Exception $e): void
    {
    }
}
