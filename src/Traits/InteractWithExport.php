<?php

namespace Iqbalatma\LaravelExportImport\Traits;

use App\Services\Management\UserService;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Iqbalatma\LaravelExportImport\Exceptions\PathGeneratorException;
use Iqbalatma\LaravelExportImport\Interfaces\PathGenerator;
use Iqbalatma\LaravelExportImport\Models\Export;
use Throwable;

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
     * @return UserService|InteractWithExport
     * @throws Throwable
     */
    protected function createExportEntity(string $exportType, string $exportName = null, string $permissionName = null, Closure $callback = null): self
    {
        DB::transaction(function () use ($exportType, $exportName, $permissionName, $callback) {
            if (is_null($exportName)) {
                $exportName = $exportType;
            }

            $pathGenerator = app(PathGenerator::class);
            $path = rtrim(implode(DIRECTORY_SEPARATOR, [$pathGenerator::getExportPath(), Str::slug($exportType)]), "/");
            $this->export = self::getExportModel()::query()->create([
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


    /**
     * @return Export|string
     */
    public static function getExportModel(): Export|string
    {
        return config("export_import.models.export");
    }
}
