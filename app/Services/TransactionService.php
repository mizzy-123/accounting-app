<?php

namespace App\Services;

use App\Exceptions\UnbalancedTransactionException;
use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TransactionService
{
    /**
     * @param  array{date: string, description?: string|null, amount: numeric-string|float|int, account_id: string, category_id: string}  $data
     */
    public function createIncome(Entity $entity, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($entity, $user, $data) {
            $amount = $this->normalizeAmount($data['amount']);
            $assetAccount = $this->resolveAccount($entity, $data['account_id'], ['asset']);
            $revenueAccount = $this->resolveAccountByName($entity, 'Pendapatan', ['revenue']);
            $category = $this->resolveCategory($entity, $data['category_id'], 'income');

            $transaction = $this->createTransactionHeader($entity, $user, [
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'type' => 'income',
                'amount' => $amount,
                'category_id' => $category->id,
            ]);

            $this->createBalancedEntries($transaction, [
                ['account_id' => $assetAccount->id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $revenueAccount->id, 'debit' => 0, 'kredit' => $amount],
            ]);

            return $transaction->load(['entries.account', 'category', 'attachments']);
        });
    }

    /**
     * @param  array{date: string, description?: string|null, amount: numeric-string|float|int, account_id: string, category_id: string}  $data
     */
    public function createExpense(Entity $entity, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($entity, $user, $data) {
            $amount = $this->normalizeAmount($data['amount']);
            $assetAccount = $this->resolveAccount($entity, $data['account_id'], ['asset']);
            $expenseAccount = $this->resolveAccountByName($entity, 'Beban', ['expense']);
            $category = $this->resolveCategory($entity, $data['category_id'], 'expense');

            $transaction = $this->createTransactionHeader($entity, $user, [
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'type' => 'expense',
                'amount' => $amount,
                'category_id' => $category->id,
            ]);

            $this->createBalancedEntries($transaction, [
                ['account_id' => $expenseAccount->id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $assetAccount->id, 'debit' => 0, 'kredit' => $amount],
            ]);

            return $transaction->load(['entries.account', 'category', 'attachments']);
        });
    }

    /**
     * @param  array{date: string, description?: string|null, amount: numeric-string|float|int, from_account_id: string, to_account_id: string}  $data
     */
    public function createTransfer(Entity $entity, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($entity, $user, $data) {
            $amount = $this->normalizeAmount($data['amount']);
            $fromAccount = $this->resolveAccount($entity, $data['from_account_id'], ['asset']);
            $toAccount = $this->resolveAccount($entity, $data['to_account_id'], ['asset']);

            if ($fromAccount->id === $toAccount->id) {
                throw new InvalidArgumentException('Akun sumber dan tujuan tidak boleh sama.');
            }

            $transaction = $this->createTransactionHeader($entity, $user, [
                'date' => $data['date'],
                'description' => $data['description'] ?? 'Transfer antar akun',
                'type' => 'transfer',
                'amount' => $amount,
            ]);

            $this->createBalancedEntries($transaction, [
                ['account_id' => $toAccount->id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $fromAccount->id, 'debit' => 0, 'kredit' => $amount],
            ]);

            return $transaction->load(['entries.account', 'attachments']);
        });
    }

    /**
     * @param  array{
     *     date: string,
     *     description?: string|null,
     *     amount: numeric-string|float|int,
     *     from_account_id: string,
     *     to_account_id: string,
     *     to_entity_id: string
     * }  $data
     * @return array{0: Transaction, 1: Transaction}
     */
    public function createInterEntityTransfer(Entity $fromEntity, Entity $toEntity, User $user, array $data): array
    {
        if ($fromEntity->id === $toEntity->id) {
            throw new InvalidArgumentException('Entity sumber dan tujuan harus berbeda.');
        }

        return DB::transaction(function () use ($fromEntity, $toEntity, $user, $data) {
            $amount = $this->normalizeAmount($data['amount']);
            $reference = (string) Str::uuid();

            $fromAsset = $this->resolveAccount($fromEntity, $data['from_account_id'], ['asset']);
            $toAsset = $this->resolveAccount($toEntity, $data['to_account_id'], ['asset']);
            $ownerDraw = $this->resolveAccountByName($fromEntity, 'Owner Draw', ['equity']);
            $modal = $this->resolveAccountByName($toEntity, 'Modal', ['equity']);

            $description = $data['description'] ?? "Transfer antar entity: {$fromEntity->name} → {$toEntity->name}";

            $outgoing = $this->createTransactionHeader($fromEntity, $user, [
                'date' => $data['date'],
                'description' => $description,
                'type' => 'inter_entity_transfer',
                'amount' => $amount,
                'reference' => $reference,
            ]);

            $this->createBalancedEntries($outgoing, [
                ['account_id' => $ownerDraw->id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $fromAsset->id, 'debit' => 0, 'kredit' => $amount],
            ]);

            $incoming = $this->createTransactionHeader($toEntity, $user, [
                'date' => $data['date'],
                'description' => $description,
                'type' => 'inter_entity_transfer',
                'amount' => $amount,
                'reference' => $reference,
            ]);

            $this->createBalancedEntries($incoming, [
                ['account_id' => $toAsset->id, 'debit' => $amount, 'kredit' => 0],
                ['account_id' => $modal->id, 'debit' => 0, 'kredit' => $amount],
            ]);

            return [
                $outgoing->load(['entries.account']),
                $incoming->load(['entries.account']),
            ];
        });
    }

    /**
     * @param  array{
     *     date: string,
     *     description?: string|null,
     *     entries: array<int, array{account_id: string, debit?: numeric-string|float|int, kredit?: numeric-string|float|int}>
     * }  $data
     */
    public function createAdjustment(Entity $entity, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($entity, $user, $data) {
            $normalizedEntries = collect($data['entries'])->map(function (array $entry) use ($entity) {
                $account = $this->resolveAccount($entity, $entry['account_id'], ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $debit = $this->normalizeEntryAmount($entry['debit'] ?? 0);
                $kredit = $this->normalizeEntryAmount($entry['kredit'] ?? 0);

                return [
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'kredit' => $kredit,
                ];
            })->all();

            $totalAmount = collect($normalizedEntries)->max(fn (array $entry) => max($entry['debit'], $entry['kredit']));

            $transaction = $this->createTransactionHeader($entity, $user, [
                'date' => $data['date'],
                'description' => $data['description'] ?? 'Jurnal penyesuaian manual',
                'type' => 'adjustment',
                'amount' => $totalAmount,
            ]);

            $this->createBalancedEntries($transaction, $normalizedEntries);

            return $transaction->load(['entries.account', 'attachments']);
        });
    }

    public function attachFile(Transaction $transaction, UploadedFile $file): void
    {
        $path = $file->store("attachments/{$transaction->entity_id}", 'local');

        $transaction->attachments()->create([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function generateFromRecurring(RecurringTransaction $recurring): Transaction
    {
        return DB::transaction(function () use ($recurring) {
            $entity = $recurring->entity;
            $user = $recurring->creator;
            $template = $recurring->template;

            $transaction = match ($template['type']) {
                'income' => $this->createIncome($entity, $user, [
                    'date' => $recurring->next_run_at->toDateString(),
                    'description' => $recurring->description,
                    'amount' => $template['amount'],
                    'account_id' => $template['account_id'],
                    'category_id' => $template['category_id'],
                ]),
                'expense' => $this->createExpense($entity, $user, [
                    'date' => $recurring->next_run_at->toDateString(),
                    'description' => $recurring->description,
                    'amount' => $template['amount'],
                    'account_id' => $template['account_id'],
                    'category_id' => $template['category_id'],
                ]),
                'transfer' => $this->createTransfer($entity, $user, [
                    'date' => $recurring->next_run_at->toDateString(),
                    'description' => $recurring->description,
                    'amount' => $template['amount'],
                    'from_account_id' => $template['from_account_id'],
                    'to_account_id' => $template['to_account_id'],
                ]),
                default => throw new InvalidArgumentException("Tipe recurring tidak dikenali: {$template['type']}"),
            };

            $transaction->update(['recurring_transaction_id' => $recurring->id]);

            $recurring->update([
                'next_run_at' => $this->nextRunDate($recurring->next_run_at, $recurring->frequency),
            ]);

            return $transaction;
        });
    }

    /**
     * @param  array<int, array{account_id: string, debit: string, kredit: string}>  $entries
     */
    public function createBalancedEntries(Transaction $transaction, array $entries): void
    {
        $this->validateEntryRows($entries);
        $this->validateBalance($entries);

        foreach ($entries as $entry) {
            $transaction->entries()->create($entry);
        }
    }

    /**
     * @param  array<int, array{debit?: numeric-string|float|int, kredit?: numeric-string|float|int}>  $entries
     */
    public function validateBalance(array $entries): void
    {
        $totalDebit = collect($entries)->sum(fn (array $entry) => (float) ($entry['debit'] ?? 0));
        $totalKredit = collect($entries)->sum(fn (array $entry) => (float) ($entry['kredit'] ?? 0));

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            throw UnbalancedTransactionException::entries();
        }
    }

    /**
     * @param  array<int, array{debit?: numeric-string|float|int, kredit?: numeric-string|float|int}>  $entries
     */
    public function validateEntryRows(array $entries): void
    {
        foreach ($entries as $entry) {
            $debit = (float) ($entry['debit'] ?? 0);
            $kredit = (float) ($entry['kredit'] ?? 0);

            if (($debit > 0 && $kredit > 0) || ($debit <= 0 && $kredit <= 0)) {
                throw UnbalancedTransactionException::invalidEntryRow();
            }
        }
    }

    private function createTransactionHeader(Entity $entity, User $user, array $attributes): Transaction
    {
        return Transaction::create([
            'entity_id' => $entity->id,
            'date' => $attributes['date'],
            'description' => $attributes['description'] ?? null,
            'type' => $attributes['type'],
            'amount' => $attributes['amount'],
            'category_id' => $attributes['category_id'] ?? null,
            'status' => 'draft',
            'created_by' => $user->id,
            'reference' => $attributes['reference'] ?? null,
        ]);
    }

    /**
     * @param  list<string>  $allowedTypes
     */
    private function resolveAccount(Entity $entity, string $accountId, array $allowedTypes): Account
    {
        $account = Account::query()
            ->forEntity($entity)
            ->active()
            ->whereKey($accountId)
            ->first();

        if (! $account || ! in_array($account->type, $allowedTypes, true)) {
            throw new InvalidArgumentException('Akun tidak valid untuk transaksi ini.');
        }

        return $account;
    }

    /**
     * @param  list<string>  $allowedTypes
     */
    private function resolveAccountByName(Entity $entity, string $name, array $allowedTypes): Account
    {
        $account = Account::query()
            ->forEntity($entity)
            ->active()
            ->where('name', $name)
            ->whereIn('type', $allowedTypes)
            ->first();

        if (! $account) {
            throw new InvalidArgumentException("Akun default '{$name}' belum tersedia untuk entity ini.");
        }

        return $account;
    }

    private function resolveCategory(Entity $entity, string $categoryId, string $type): Category
    {
        $category = Category::query()
            ->forEntity($entity)
            ->whereKey($categoryId)
            ->where('type', $type)
            ->first();

        if (! $category) {
            throw new InvalidArgumentException('Kategori tidak valid untuk transaksi ini.');
        }

        return $category;
    }

    private function normalizeAmount(float|int|string $amount): string
    {
        $normalized = number_format((float) $amount, 2, '.', '');

        if ((float) $normalized <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari nol.');
        }

        return $normalized;
    }

    private function normalizeEntryAmount(float|int|string $amount): string
    {
        $normalized = number_format((float) $amount, 2, '.', '');

        if ((float) $normalized < 0) {
            throw new InvalidArgumentException('Jumlah entry tidak boleh negatif.');
        }

        return $normalized;
    }

    private function nextRunDate(Carbon $current, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $current->copy()->addDay(),
            'weekly' => $current->copy()->addWeek(),
            'monthly' => $current->copy()->addMonth(),
            'yearly' => $current->copy()->addYear(),
            default => throw new InvalidArgumentException("Frekuensi tidak dikenali: {$frequency}"),
        };
    }
}
