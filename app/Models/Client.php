<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $entity_id
 * @property string $name
 * @property string|null $contact_info
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int $projects_count
 */
class Client extends Model
{
    use HasUuids;

    protected $fillable = ['entity_id', 'name', 'contact_info'];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope ke entity aktif.
     */
    public function scopeForEntity($query, Entity|string $entity): mixed
    {
        $id = $entity instanceof Entity ? $entity->id : $entity;

        return $query->where('entity_id', $id);
    }
}
