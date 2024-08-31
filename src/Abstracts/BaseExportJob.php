<?php

namespace Iqbalatma\LaravelExportImport\Abstracts;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Iqbalatma\LaravelExportImport\Models\Export;

abstract class BaseExportJob
{
    public int $timeout = 1200;
    /** @var resource|null */
    protected $file;

    protected array $header = [];

    /**
     * Create a new job instance.
     */
    public function __construct(protected Export $export)
    {
    }


    /**
     * @return $this
     */
    protected function checkIsDirectoryExists(): self
    {
        File::ensureDirectoryExists(storage_path("app/tmp"));
        return $this;
    }


    /**
     * @return $this
     */
    protected function exportComplete(): self
    {
        $this->export->is_completed = true;
        $this->export->exported_at = Carbon::now();
        $this->export->available_until = Carbon::now()->addHours(config("app.export_available_until"));
        $this->export->save();
        fclose($this->file);

        $this->uploadFileToS3()
            ->deleteTmpFile();
        return $this;
    }


    /**
     * @return $this
     */
    protected function setFile(): self
    {
        $this->file = fopen(storage_path("app/tmp/{$this->export->filename}"), mode: "w");
        fputcsv($this->file, $this->getHeader());
        return $this;
    }


    /**
     * @return BaseExportJob
     */
    private function uploadFileToS3(): self
    {
        Storage::disk("s3")->putFileAs(
            $this->export->path,
            storage_path("app/tmp/{$this->export->filename}"),
            $this->export->filename
        );

        return $this;
    }

    /**
     * @return void
     */
    private function deleteTmpFile(): void
    {
        Storage::delete("tmp/{$this->export->filename}");
    }

    /**
     * @return array
     */
    protected function getHeader(): array
    {
        return $this->header;
    }
}
