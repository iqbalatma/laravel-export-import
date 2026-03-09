<?php

namespace Iqbalatma\LaravelExportImport\Abstracts;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Iqbalatma\LaravelExportImport\ImportStatus;
use Iqbalatma\LaravelExportImport\Models\Import;
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
    protected array $header;
    public string $temporaryPath;


    public function __construct(protected Import $import)
    {
        $this->totalRow = $this->failedRow = $this->successRow = 0;
        $this->isFirstRowSkipped = false;
        $this->isFileErrorExists = false;
        $this->user = $this->import->imported_by;
        $this->header = [];
        $this->temporaryPath = config("export_import.path.temporary");
    }

    public function handle(): void
    {
        try {
            $this->setFile()
                ->readFile()
                ->importComplete();

            $this->afterImport();
        } catch (Exception $e) {
            $this->importFailed(get_class($e) . " : " . $e->getMessage());
            throw new RuntimeException($e);
        }finally {
            if (is_resource($this->file)) {
                fclose($this->file);
            }

            if (is_resource($this->errorFile)){
                fclose($this->errorFile);
            }
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
                storage_path("app/$this->temporaryPath/errors/{$this->import->failed_filename}"),
                $this->import->failed_filename
            );
        }
    }

    /**
     * @return void
     */
    private function deleteTmpFile(): void
    {
        Storage::delete("$this->temporaryPath/{$this->import->filename}");

        if ($this->isFileErrorExists) {
            Storage::delete("/$this->temporaryPath/errors/{$this->import->failed_filename}");
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
            Storage::put("$this->temporaryPath/{$this->import->filename}", $file);
            $this->file = fopen(storage_path("app/$this->temporaryPath/{$this->import->filename}"), mode: "r");
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
            File::ensureDirectoryExists(storage_path("app/$this->temporaryPath/errors"));
            $this->errorFile = fopen(storage_path("app/$this->temporaryPath/errors/error-{$this->import->filename}"), mode: "w");
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
            $this->import->failed_path =  "{$this->import->path}/errors";
            $this->import->failed_filename = "error-{$this->import->filename}";
            $this->import->failed_full_path = "{$this->import->failed_path}/{$this->import->failed_filename}" ;
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
}
