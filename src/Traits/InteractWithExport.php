<?php

namespace Iqbalatma\LaravelExportImport\Traits;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait InteractWithExport
{
    protected Carbon $exportStartDate;
    protected Carbon $exportEndDate;
    protected $export;

    protected int $diffLimit = 31;


    /**
     * @throws Exception
     */
    protected function checkExportDateRange(): self
    {
        $this->exportStartDate = Carbon::parse(request()->input("start_date"))->startOfDay();
        $this->exportEndDate = Carbon::parse(request()->input("end_date"))->endOfDay();

        $diff = $this->exportStartDate->diffInDays($this->exportEndDate);
        if ($diff > $this->diffLimit) {
            throw ValidationException::withMessages(["error" => "Date range cannot be greater than 31 days"]);
        }
        return $this;
    }

    /**
     * @param string $exportType
     * @param string|null $exportName
     * @param string|null $permissionName
     * @param Closure|null $callback
     * @return InteractWithExport
     */
    protected function createExportEntity(string $exportType, string $exportName = null, string $permissionName = null, Closure $callback = null): self
    {
        DB::transaction(function () use ($exportType, $exportName, $permissionName, $callback) {
            if (is_null($exportName)) {
                $exportName = $exportType;
            }

            $path = rtrim(implode(DIRECTORY_SEPARATOR, [config("export_import.path.export_path"), Str::slug($exportType)]), "/");
            $this->export = config("export_import.models.export")::query()->create([
                "name" => $exportName,
                "type" => $exportType,
                "path" => $path,
                "filename" => $filename = Str::uuid() . ".csv",
                "full_path" => "$path/$filename",
                "permission_name" => $permissionName,
                "exported_by_id" => Auth::id(),
                "exported_at" => null,
                "is_completed" => false,
            ]);
            if (is_callable($callback)) {
                $callback();
            }
        });

        return $this;
    }
}
