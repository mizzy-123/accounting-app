<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $fixed_asset_id
 * @property string $transaction_id
 * @property Carbon $period
 * @property string $amount
 */
class DepreciationEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'fixed_asset_id',
        'transaction_id',
        'period',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
