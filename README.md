# Laravel Export Import

A simple and extensible Laravel package for handling **large data exports and imports** using a **queue-based architecture**.

This package is designed to export and import large datasets efficiently while keeping **memory usage low** by leveraging Laravel's `LazyCollection` and queued jobs.

---

## Features

- Queue based export and import jobs
- Memory efficient processing using `LazyCollection`
- CSV export support
- Extensible architecture using abstract classes
- Automatic temporary file handling
- Configurable filesystem disk support
- Designed for large datasets

---

## Requirements

- PHP >= 8.3
- Laravel >= 10

---

# Installation

Install the package via Composer.

```bash
composer require iqbalatma/laravel-export-import
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=export-import-config
```

Publish migrations:

```bash
php artisan vendor:publish --tag=migrations
```

Run migrations:

```bash
php artisan migrate
```

---

# Configuration

After publishing, the configuration file will be available at:

```
config/export_import.php
```

Example configuration:

```php
<?php

use Iqbalatma\LaravelExportImport\Models\Export;
use Iqbalatma\LaravelExportImport\Models\Import;
use Iqbalatma\LaravelExportImport\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the models that will be used by the package.
    | This allows you to override the default models with your own
    | implementations if necessary.
    |
    */

    "models" => [
        "user" => User::class,
        "import" => Import::class,
        "export" => Export::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    |
    | Define the directories used for storing export and import files.
    | Temporary files will be generated during processing before being
    | moved to the final disk storage.
    |
    */

    "path" => [
        "export_path" => "exports",
        "import_path" => "imports",
        "temporary" => "tmp",
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Disk
    |--------------------------------------------------------------------------
    |
    | The disk that will be used to store uploaded import files.
    | This should correspond to one of the disks defined in the
    | "filesystems" configuration file.
    |
    */

    "import_disk" => env("EXPORT_IMPORT_IMPORT_DISK", "s3"),

    /*
    |--------------------------------------------------------------------------
    | Export Disk
    |--------------------------------------------------------------------------
    |
    | The disk where generated export files will be stored.
    | You may configure this to use local, s3, or any supported
    | filesystem disk.
    |
    */

    "export_disk" => env("EXPORT_IMPORT_EXPORT_DISK", "s3"),

    /*
    |--------------------------------------------------------------------------
    | Export Availability (Hours)
    |--------------------------------------------------------------------------
    |
    | Determines how long exported files remain available for download
    | before they expire. The value is defined in hours.
    |
    */

    "export_available_until" => 72,
    
    
    /*
    |--------------------------------------------------------------------------
    | Job Default Timeout (Seconds)
    |--------------------------------------------------------------------------
    |
    | Determines how long job timeout. This value is defined in seconds
    |
    */

    "job_timeout" => 1200,

];

```

---

## Models

Defines the models used internally by the package.

| Key | Description |
|-----|-------------|
| user | The user model responsible for triggering import or export processes |
| import | Model used to store import job information |
| export | Model used to store export job information |

You may replace these models with your own implementations.

---

## Paths

Defines directories used during import and export processes.

| Key | Description |
|-----|-------------|
| export_path | Directory where exported files are stored |
| import_path | Directory where uploaded import files are stored |
| temporary | Temporary directory used while processing files |

Temporary files are automatically cleaned up after processing.

---

## Export/Import Disk

```
"export_disk" => "s3"
"import_disk" => "s3"
```

Specifies which filesystem disk will store **import files**.

This disk must exist in:

```
config/filesystems.php
```

Example disks:

- local
- public
- s3

---

## Export Availability

```
"export_available_until" => 72
```

Defines how long exported files remain available for download.

Value is defined in **hours**.

Examples:

| Value | Meaning |
|------|--------|
| 24 | File expires after 1 day |
| 72 | File expires after 3 days |
| 168 | File expires after 7 days |

---
# How to export
To start export data you can use trait InteractWithExport
```php
use \Iqbalatma\LaravelExportImport\Traits\InteractWithExport
```

Example : 
```php
<?php

namespace App\Services;

use App\Jobs\Exports\ExportUserJob;
use \Iqbalatma\LaravelExportImport\Traits\InteractWithExport
use Illuminate\Contracts\Container\BindingResolutionException;

class UserService
{
    use InteractWithExport;

    /**
     * @param array $requestedData
     * @return bool
     * @throws BindingResolutionException|AdmissionException|\Throwable
     */
    public static function handle(array $requestedData): bool
    {
        $service = new static();

        $service->createExportEntity(
            exportType: "USER",
            exportName: "Export User"
            permissionName: "can.access.user",
            callback: function () {
                ExportUserJob::dispatch($service->export);
            }
        );
        return true;
    }

}


namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\LazyCollection;
use Iqbalatma\LaravelExportImport\Abstracts\BaseExportJob;

class ExportUserJob extends BaseExportJob
{
    protected LazyCollection $users;

    protected array $header = [
        'name',
        'email'
    ];

    protected function executeQuery(): self
    {
        $this->users = User::query()->lazy(200);

        return $this;
    }

    protected function writeFile(): self
    {
        foreach ($this->users as $user) {
            fputcsv($this->file, [
                $user->name,
                $user->email
            ]);
        }

        return $this;
    }
}

```

---

### Export Workflow

The export process follows this workflow:

```
checkIsDirectoryExists
        ↓
setFile
        ↓
executeQuery
        ↓
writeFile
        ↓
exportComplete
        ↓
afterExport

```

This workflow ensures that exports are handled efficiently and safely.

---

### Export Lifecycle Hooks

You can override lifecycle hooks to customize the export process.

### After Export

```php
protected function afterExport(): void
{
    // Example: send notification
}
```






---
# How to Import
To start export data you can use trait InteractWithImport
```php
use \Iqbalatma\LaravelExportImport\Traits\InteractWithImport
```


Example :
```php
<?php

namespace App\Services;

use App\Jobs\Exports\ImportUserJob;
use \Iqbalatma\LaravelExportImport\Traits\InteractWithImport
use Illuminate\Contracts\Container\BindingResolutionException;

class UserService
{
    use InteractWithImport;

    /**
     * @param array $requestedData
     * @return bool
     * @throws BindingResolutionException|AdmissionException|\Throwable
     */
    public static function handle(array $requestedData): bool
    {
        $service = new static();

        $file = request()?->file("file");
        if (!$file) {
            throw ValidationException::withMessages(["file" => "Required file not found"]);
        }
        $service->createImportEntity(
            file: $file,
            importType: "USER",
            importName: "Import User"
            permissionName: "can.access.user",
            callback: function () {
                ImportUserJob::dispatch($service->import);
            }
        );
        return true;
    }

}


namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\LazyCollection;
use Iqbalatma\LaravelExportImport\Abstracts\BaseImportJob;

class ImportUserJob extends BaseImportJob
{
    protected function readFile(): self
    {
        $this->getLazyCollection()
            ->chunk(200)
            ->each(function (LazyCollection $collection) {
                foreach ($collection as $row) {
                    try {
                        DB::beginTransaction();
                        #get data from db
                        /** @var User $userFromDB */
                        $userFromDB = User::query()->create($row);

                        $this->successRow++;
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        $this->generateFileError()
                            ->writeErrorRow($row, $e->getMessage());
                        $this->failedRow++;
                    } finally {
                        $this->totalRow++;
                    }
                }
            });

        return $this;
    }
}

```

---

# Contributing

Contributions are welcome.

1. Fork the repository
2. Create a new branch
3. Submit a pull request

---

# License

This package is open-sourced software licensed under the **MIT license**.
