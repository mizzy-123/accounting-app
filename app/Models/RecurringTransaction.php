<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $entity_id
 * @property string $created_by
 * @property string $description
 * @property array $template
 * @property string $frequency
 * @property string $next_run_at
 * @property string|null $ends_at
 * @property bool $is_active
 */
class RecurringTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id',
        'created_by',
        'description',
        'template',
        'frequency',
        'next_run_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'template' => 'array',
            'next_run_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeDue($query)
    {
        return $query
            ->where('is_active', true)
            ->whereDate('next_run_at', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', now()->toDateString());
            });
    }
}
