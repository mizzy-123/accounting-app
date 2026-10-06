<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\FixedAsset;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DepreciationService
{
    public function __construct(private TransactionService $transactions) {}

    /**
     * @param  array{
     *     name: string,
     *     asset_account_id: string,
     *     accumulated_account_id: string,
     *     expense_account_id: string,
     *     payment_account_id?: string|null,
     *     acquisition_date: string,
     *     cost: numeric-string|float|int,
     *     residual_value: numeric-string|float|int,
     *     useful_life_months: int,
     *     notes?: string|null
     * }  $data
     */
    public function create(Entity $entity, User $user, array $data): FixedAsset
    {
        return DB::transaction(function () use ($entity, $user, $data): FixedAsset {
            $asset = FixedAsset::create([
                'entity_id' => $entity->id,
                'name' => $data['name'],
                'asset_account_id' => $data['asset_account_id'],
                'accumulated_account_id' => $data['accumulated_account_id'],
                'expense_account_id' => $data['expense_account_id'],
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'acquisition_date' => $data['acquisition_date'],
                'cost' => $data['cost'],
                'residual_value' => $data['residual_value'],
                'useful_life_months' => $data['useful_life_months'],
                'method' => 'straight_line',
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['payment_account_id'])) {
                $this->postAcquisition($asset, $entity, $user);
            }

            return $asset->fresh(['assetAccount', 'accumulatedAccount', 'expenseAccount']);
        });
    }

    /**
     * Catat penyusutan garis lurus sampai bulan `through` (YYYY-MM).
     */
    public function postThrough(FixedAsset $asset, User $user, string $throughYm): int
    {
        if ($asset->status !== 'active') {
            throw new InvalidArgumentException('Aset ini sudah tidak aktif untuk disusutkan.');
        }

        $through = CarbonImmutable::createFromFormat('Y-m', $throughYm)->startOfMonth();
        $cursor = $asset->acquisition_date->toImmutable()->startOfMonth();
        $posted = 0;

        while ($cursor->lte($through)) {
            $exists = $asset->depreciationEntries()
                ->whereDate('period', $cursor->toDateString())
                ->exists();

            if (! $exists) {
                $amount = $this->amountForNextPeriod($asset);

                if (bccomp($amount, '0.00', 2) <= 0) {
                    $asset->update(['status' => 'fully_depreciated']);
                    break;
                }

                $this->postPeriod($asset, $user, $cursor, $amount);
                $posted++;

                if (bccomp($asset->fresh()->bookValue(), (string) $asset->residual_value, 2) <= 0) {
                    $asset->update(['status' => 'fully_depreciated']);
                    break;
                }
            }

            $cursor = $cursor->addMonth();
        }

        return $posted;
    }

    /**
     * @param  array{
     *     date: string,
     *     proceeds: numeric-string|float|int,
     *     proceeds_account_id?: string|null,
     *     gain_account_id: string,
     *     loss_account_id: string
     * }  $data
     */
    public function dispose(FixedAsset $asset, User $user, array $data): FixedAsset
    {
        if (! in_array($asset->status, ['active', 'fully_depreciated'], true)) {
            throw new InvalidArgumentException('Aset ini sudah dilepas.');
        }

        $disposalDate = CarbonImmutable::parse($data['date']);

        if ($asset->status === 'active') {
            $this->postThrough($asset->fresh(), $user, $disposalDate->format('Y-m'));
            $asset->refresh();
        }

        $proceeds = number_format((float) $data['proceeds'], 2, '.', '');
        $accumulated = $asset->accumulatedAmount();
        $cost = number_format((float) $asset->cost, 2, '.', '');
        $bookValue = $asset->bookValue();
        $difference = bcsub($proceeds, $bookValue, 2);

        $entries = [];

        if (bccomp($accumulated, '0.00', 2) > 0) {
            $entries[] = [
                'account_id' => $asset->accumulated_account_id,
                'debit' => $accumulated,
                'kredit' => 0,
            ];
        }

        if (bccomp($proceeds, '0.00', 2) > 0) {
            if (empty($data['proceeds_account_id'])) {
                throw new InvalidArgumentException('Akun penerimaan hasil penjualan wajib dipilih.');
            }

            $entries[] = [
                'account_id' => $data['proceeds_account_id'],
                'debit' => $proceeds,
                'kredit' => 0,
            ];
        }

        if (bccomp($difference, '0.00', 2) < 0) {
            $entries[] = [
                'account_id' => $data['loss_account_id'],
                'debit' => bcmul($difference, '-1', 2),
                'kredit' => 0,
            ];
        }

        $entries[] = [
            'account_id' => $asset->asset_account_id,
            'debit' => 0,
            'kredit' => $cost,
        ];

        if (bccomp($difference, '0.00', 2) > 0) {
            $entries[] = [
                'account_id' => $data['gain_account_id'],
                'debit' => 0,
                'kredit' => $difference,
            ];
        }

        return DB::transaction(function () use ($asset, $user, $disposalDate, $entries): FixedAsset {
            $transaction = $this->transactions->createAdjustment($asset->entity, $user, [
                'date' => $disposalDate->toDateString(),
                'description' => "Pelepasan aset {$asset->name}",
                'reference' => "asset-disposal:{$asset->id}",
                'entries' => $entries,
            ]);

            $this->markPosted($transaction, $user);

            $asset->update(['status' => 'disposed']);

            return $asset->fresh();
        });
    }

    private function amountForNextPeriod(FixedAsset $asset): string
    {
        $asset->refresh();
        $remaining = bcsub($asset->depreciableAmount(), $asset->accumulatedAmount(), 2);
        $postedCount = $asset->depreciationEntries()->count();

        if (bccomp($remaining, '0.00', 2) <= 0 || $postedCount >= $asset->useful_life_months) {
            return '0.00';
        }

        if ($postedCount === $asset->useful_life_months - 1) {
            return $remaining;
        }

        $monthly = $asset->monthlyAmount();

        return bccomp($remaining, $monthly, 2) < 0 ? $remaining : $monthly;
    }

    private function postPeriod(FixedAsset $asset, User $user, DateTimeInterface $period, string $amount): void
    {
        $period = CarbonImmutable::parse($period);

        $transaction = $this->transactions->createAdjustment($asset->entity, $user, [
            'date' => $period->copy()->endOfMonth()->toDateString(),
            'description' => "Penyusutan {$asset->name} {$period->format('Y-m')}",
            'reference' => "depreciation:{$asset->id}:{$period->format('Y-m')}",
            'entries' => [
                ['account_id' => $asset->expense_account_id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $asset->accumulated_account_id, 'debit' => 0, 'kredit' => $amount],
            ],
        ]);

        $this->markPosted($transaction, $user);

        $asset->depreciationEntries()->create([
            'transaction_id' => $transaction->id,
            'period' => $period->toDateString(),
            'amount' => $amount,
        ]);
    }

    private function postAcquisition(FixedAsset $asset, Entity $entity, User $user): void
    {
        $transaction = $this->transactions->createAdjustment($entity, $user, [
            'date' => $asset->acquisition_date->toDateString(),
            'description' => "Perolehan aset {$asset->name}",
            'reference' => "asset-acquisition:{$asset->id}",
            'entries' => [
                ['account_id' => $asset->asset_account_id, 'debit' => $asset->cost, 'kredit' => 0],
                ['account_id' => $asset->payment_account_id, 'debit' => 0, 'kredit' => $asset->cost],
            ],
        ]);

        $this->markPosted($transaction, $user);
    }

    private function markPosted(Transaction $transaction, User $user): void
    {
        $transaction->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }
}
