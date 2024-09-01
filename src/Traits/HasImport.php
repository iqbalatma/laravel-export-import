<?php

namespace Iqbalatma\LaravelExportImport\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Iqbalatma\LaravelExportImport\Models\Import;

/**
 * @property string import_id
 * @property Import import
 */
trait HasImport
{
    protected string $importIdColumn = "import_id";
    /**
     * @return string
     */
    public function getImportColumnName(): string
    {
        return $this->importIdColumn;
    }

    /**
     * @return BelongsTo
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(config("export_import.models.import"), $this->getImportColumnName(), "id");
    }
}
