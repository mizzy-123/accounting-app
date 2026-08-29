<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $attachable_type
 * @property string $attachable_id
 * @property string $file_path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size
 */
class Attachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
