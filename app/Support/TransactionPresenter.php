<?php

namespace App\Support;

use App\Models\Transaction;

class TransactionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date->toDateString(),
            'description' => $transaction->description,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'category' => $transaction->category ? [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
            ] : null,
            'creator' => $transaction->creator ? [
                'id' => $transaction->creator->id,
                'name' => $transaction->creator->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(Transaction $transaction): array
    {
        return [
            ...self::summary($transaction),
            'reference' => $transaction->reference,
            'entries' => $transaction->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'account' => [
                    'id' => $entry->account->id,
                    'name' => $entry->account->name,
                    'type' => $entry->account->type,
                ],
                'debit' => $entry->debit,
                'kredit' => $entry->kredit,
            ])->values(),
            'attachments' => $transaction->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'url' => route('transactions.attachments.show', [
                    'transaction' => $transaction->id,
                    'attachment' => $attachment->id,
                ]),
            ])->values(),
        ];
    }
}
