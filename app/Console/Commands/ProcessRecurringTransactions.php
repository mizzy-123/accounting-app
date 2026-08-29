<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Services\TransactionService;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'accounting:process-recurring';

    protected $description = 'Generate transactions from due recurring templates';

    public function handle(TransactionService $transactionService): int
    {
        $due = RecurringTransaction::query()->due()->with(['entity', 'creator'])->get();

        if ($due->isEmpty()) {
            $this->info('Tidak ada transaksi berulang yang jatuh tempo.');

            return self::SUCCESS;
        }

        foreach ($due as $recurring) {
            $transaction = $transactionService->generateFromRecurring($recurring);
            $this->line("Generated transaction {$transaction->id} from recurring {$recurring->id}");
        }

        $this->info("Selesai memproses {$due->count()} transaksi berulang.");

        return self::SUCCESS;
    }
}
