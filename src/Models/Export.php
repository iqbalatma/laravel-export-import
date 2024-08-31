<?php

namespace Iqbalatma\LaravelExportImport\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string id
 * @property string name
 * @property string type
 * @property string path
 * @property string filename
 * @property string full_path
 * @property string permission_name
 * @property string exported_by_id
 * @property boolean is_completed
 * @property Carbon available_until
 * @property Carbon exported_at
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Export extends Model
{
    use HasUuids;

    protected $table = "exports";
    protected $fillable = [
        "name", "type", "filename", "permission_name", "exported_by_id",
        "available_until", "exported_at", "is_completed", "path", "full_path"
    ];

    /**
     * @return BelongsTo
     */
    public function exported_by(): BelongsTo
    {
        return $this->belongsTo(config("export_import.models.user"), "exported_by_id", "id");
    }
}
