<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $type 'personal' | 'business'
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
    public function scopePersonal(Builder $query): Builder
    {
        return $query->where('type', 'personal');
    }

    /**
     * Scope: entity bertipe business.
     */
    public function scopeBusiness(Builder $query): Builder
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

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function bankImportRules(): HasMany
    {
        return $this->hasMany(BankImportRule::class);
    }

    public function bankImportBatches(): HasMany
    {
        return $this->hasMany(BankImportBatch::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }
}
