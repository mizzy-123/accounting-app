<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property string $type  'personal' | 'business'
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Entity extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'type'];

    /**
     * Users yang tergabung dalam entity ini (via pivot entity_user).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entity_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Scope: entity bertipe personal.
     */
    public function scopePersonal(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', 'personal');
    }

    /**
     * Scope: entity bertipe business.
     */
    public function scopeBusiness(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', 'business');
    }

    public function isPersonal(): bool
    {
        return $this->type === 'personal';
    }

    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }
}
