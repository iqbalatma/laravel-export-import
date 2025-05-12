<?php

namespace Iqbalatma\LaravelExportImport\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Iqbalatma\LaravelExportImport\ImportStatus;

/**
 * @property string id
 * @property string name
 * @property int success_row
 * @property int failed_row
 * @property int total_row
 * @property string path
 * @property string filename
 * @property string full_path
 * @property string failed_path
 * @property string failed_filename
 * @property string failed_full_path
 * @property string permission_name
 * @property string imported_by_id
 * @property string|ImportStatus status
 * @property string failed_message
 * @property string original_filename
 * @property bool is_completed
 * @property Carbon imported_at
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Import extends Model
{
    use HasUuids;

    protected $table = "imports";
    protected $fillable = [
        "type", "name", "success_row", "failed_row",
        "total_row", "path", "filename","original_filename", "full_path",
        "failed_path", "failed_filename", "failed_full_path",
        "permission_name", "imported_by_id", "is_completed",
        "imported_at", "status", "failed_message"
    ];

    /**
     * @return BelongsTo
     */
    public function imported_by(): BelongsTo
    {
        return $this->belongsTo(config("export_import.models.user"), "imported_by_id", "id");
    }
}
