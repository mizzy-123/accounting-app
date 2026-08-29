<?php

namespace App\Observers;

use App\Models\TransactionEntry;
use App\Services\AuditLogger;

class TransactionEntryObserver
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function created(TransactionEntry $entry): void
    {
        $this->auditLogger->log($entry, 'created', [
            'after' => $this->snapshot($entry),
        ]);
    }

    public function updated(TransactionEntry $entry): void
    {
        $changes = $entry->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $this->auditLogger->log($entry, 'updated', [
            'before' => collect($changes)->mapWithKeys(
                fn ($value, $key) => [$key => $entry->getOriginal($key)],
            )->all(),
            'after' => $changes,
        ]);
    }

    public function deleted(TransactionEntry $entry): void
    {
        $this->auditLogger->log($entry, 'deleted', [
            'before' => $this->snapshot($entry),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(TransactionEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'transaction_id' => $entry->transaction_id,
            'account_id' => $entry->account_id,
            'debit' => $entry->debit,
            'kredit' => $entry->kredit,
        ];
    }
}
