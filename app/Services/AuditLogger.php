<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(Model $model, string $action, ?array $changes = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'entity_id' => $this->resolveEntityId($model),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => (string) $model->getKey(),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    private function resolveEntityId(Model $model): ?string
    {
        if ($model instanceof Transaction) {
            return $model->entity_id;
        }

        if ($model instanceof TransactionEntry) {
            return $model->transaction?->entity_id
                ?? Transaction::query()->whereKey($model->transaction_id)->value('entity_id');
        }

        if (isset($model->entity_id)) {
            return (string) $model->entity_id;
        }

        return null;
    }
}
