<?php

namespace Iqbalatma\LaravelExportImport\Abstracts;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Iqbalatma\LaravelExportImport\ExportStatus;
use Iqbalatma\LaravelExportImport\Models\Export;
use Iqbalatma\LaravelExportImport\Exceptions\FailedToUploadFileToDiskException;

abstract class BaseExportJob
{
    public string|null $status = null;
    public int $timeout;
    public string $temporaryPath;
    /** @var resource|null */
    protected $file;

    protected array $header = [];

    /**
     * Create a new job instance.
     */
    public function __construct(protected Export $export)
    {
        $this->timeout = config("export_import.job_timeout");
        $this->temporaryPath = config("export_import.path.temporary");
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $this->checkIsDirectoryExists()
                ->setFile()
                ->executeQuery()
                ->writeFile()
                ->exportComplete();

            $this->afterExport();
        } catch (Exception $e) {
            $this->exportFailed($e->getMessage());
            throw $e;
        } finally {
            if (is_resource($this->file)) {
                fclose($this->file);
            }
        }
    }

    /**
     * @return $this
     */
    protected function checkIsDirectoryExists(): self
    {
        File::ensureDirectoryExists(storage_path("app/$this->temporaryPath"));
        return $this;
    }


    /**
     * @return $this
     * @throws FailedToUploadFileToDiskException
     */
    protected function exportComplete(): self
    {
        $this->export->is_completed = true;
        $this->export->exported_at = Carbon::now();
        $this->export->status = $this->status ?: ExportStatus::COMPLETED->name;
        $this->export->available_until = Carbon::now()->addHours(config("export_import.export_available_until"));
        $this->export->save();

        $this->uploadFileToDisk()
            ->deleteTmpFile();
        return $this;
    }

    /**
     * @param string|null $message
     * @return $this
     */
    protected function exportFailed(string|null $message = null): self
    {
        $this->export->status = ExportStatus::FAILED->name;
        $this->export->failed_message = $message;
        $this->export->save();
        return $this;
    }

    /**
     * @return $this
     */
    protected function setFile(): self
    {
        $this->file = fopen(storage_path("app/$this->temporaryPath/{$this->export->filename}"), mode: "w");
        fputcsv($this->file, $this->getHeader());
        return $this;
    }


    /**
     * @return BaseExportJob
     * @throws FailedToUploadFileToDiskException
     */
    private function uploadFileToDisk(): self
    {
        $uploaded = Storage::disk(config("export_import.export_disk"))->putFileAs(
            $this->export->path,
            storage_path("app/$this->temporaryPath/{$this->export->filename}"),
            $this->export->filename
        );

        if (!$uploaded) {
            throw new FailedToUploadFileToDiskException();
        }

        return $this;
    }

    /**
     * @return void
     */
    private function deleteTmpFile(): void
    {
        Storage::delete("$this->temporaryPath/{$this->export->filename}");
    }

    /**
     * @return array
     */
    protected function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return self
     */
    abstract protected function executeQuery(): self;

    /**
     * @return self
     */
    abstract protected function writeFile(): self;

    /**
     * @return void
     */
    protected function afterExport(): void
    {
    }
}
