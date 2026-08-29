<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $entity_id
 * @property string $name
 * @property string $type
 */
class Category extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id',
        'name',
        'type',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeForEntity($query, Entity|string $entity)
    {
        $entityId = $entity instanceof Entity ? $entity->id : $entity;

        return $query->where('entity_id', $entityId);
    }
}
