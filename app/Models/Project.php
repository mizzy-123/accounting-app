<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $entity_id
 * @property string|null $client_id
 * @property string $name
 * @property string|null $budget
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string $status active|completed|cancelled
 */
class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id',
        'client_id',
        'name',
        'budget',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // ──────────────────── Relations ────────────────────

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // ──────────────────── Scopes ────────────────────

    public function scopeForEntity($query, Entity|string $entity): mixed
    {
        $id = $entity instanceof Entity ? $entity->id : $entity;

        return $query->where('entity_id', $id);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, string $status): mixed
    {
        return $query->where('status', $status);
    }

    // ──────────────────── Computed ────────────────────

    /**
     * Total transaksi income yang ter-tag ke project ini.
     */
    public function totalRevenue(): string
    {
        return (string) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'approved')
            ->sum('amount');
    }

    /**
     * Total transaksi expense yang ter-tag ke project ini.
     */
    public function totalExpense(): string
    {
        return (string) $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'approved')
            ->sum('amount');
    }

    /**
     * Net profit = revenue - expense.
     */
    public function netProfit(): string
    {
        return (string) (
            (float) $this->totalRevenue() - (float) $this->totalExpense()
        );
    }

    /**
     * Persentase budget terpakai (0–100).
     */
    public function budgetUsedPercent(): float
    {
        if (! $this->budget || (float) $this->budget === 0.0) {
            return 0.0;
        }

        return min(100.0, ((float) $this->totalExpense() / (float) $this->budget) * 100);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOverBudget(): bool
    {
        if (! $this->budget) {
            return false;
        }

        return (float) $this->totalExpense() > (float) $this->budget;
    }
}
