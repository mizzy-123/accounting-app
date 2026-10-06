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
 * @property string $asset_account_id
 * @property string $accumulated_account_id
 * @property string $expense_account_id
 * @property string|null $payment_account_id
 * @property Carbon $acquisition_date
 * @property string $cost
 * @property string $residual_value
 * @property int $useful_life_months
 * @property string $method
 * @property string $status
 * @property string|null $notes
 */
class FixedAsset extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id',
        'name',
        'asset_account_id',
        'accumulated_account_id',
        'expense_account_id',
        'payment_account_id',
        'acquisition_date',
        'cost',
        'residual_value',
        'useful_life_months',
        'method',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'cost' => 'decimal:2',
            'residual_value' => 'decimal:2',
            'useful_life_months' => 'integer',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function scopeForEntity($query, Entity|string $entity)
    {
        $entityId = $entity instanceof Entity ? $entity->id : $entity;

        return $query->where('entity_id', $entityId);
    }

    public function depreciableAmount(): string
    {
        return bcsub((string) $this->cost, (string) $this->residual_value, 2);
    }

    public function accumulatedAmount(): string
    {
        return number_format((float) $this->depreciationEntries()->sum('amount'), 2, '.', '');
    }

    public function bookValue(): string
    {
        return bcsub((string) $this->cost, $this->accumulatedAmount(), 2);
    }

    public function monthlyAmount(): string
    {
        if ($this->useful_life_months < 1) {
            return '0.00';
        }

        return bcdiv($this->depreciableAmount(), (string) $this->useful_life_months, 2);
    }
}
