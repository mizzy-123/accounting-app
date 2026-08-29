<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\AuditLogger;

class TransactionObserver
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function created(Transaction $transaction): void
    {
        $this->auditLogger->log($transaction, 'created', [
            'after' => $this->snapshot($transaction),
        ]);
    }

    public function updated(Transaction $transaction): void
    {
        $changes = $transaction->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $action = (($changes['status'] ?? null) === 'approved')
            ? 'approved'
            : 'updated';

        $this->auditLogger->log($transaction, $action, [
            'before' => collect($changes)->mapWithKeys(
                fn ($value, $key) => [$key => $transaction->getOriginal($key)],
            )->all(),
            'after' => $changes,
        ]);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->auditLogger->log($transaction, 'deleted', [
            'before' => $this->snapshot($transaction),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'entity_id' => $transaction->entity_id,
            'date' => $transaction->date?->toDateString(),
            'description' => $transaction->description,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'category_id' => $transaction->category_id,
            'created_by' => $transaction->created_by,
            'approved_by' => $transaction->approved_by,
        ];
    }
}
