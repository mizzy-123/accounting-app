<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Entity;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array{
     *     accounts: list<array{id: string, name: string, type: string, balance: string}>,
     *     totals: array{
     *         total_assets: string,
     *         income_this_month: string,
     *         expense_this_month: string,
     *         net_this_month: string
     *     },
     *     chart: array{
     *         weekly: list<array{label: string, income: string, expense: string}>,
     *         monthly: list<array{label: string, income: string, expense: string}>
     *     },
     *     category_expenses: list<array{name: string, amount: string}>,
     *     recent_transactions: list<array{
     *         id: string,
     *         date: string,
     *         description: string|null,
     *         type: string,
     *         amount: string
     *     }>
     * }
     */
    public function forEntity(Entity $entity): array
    {
        $accounts = $this->accountBalances($entity);
        $now = Carbon::now();

        $payload = [
            'accounts' => $accounts,
            'totals' => $this->monthlyTotals($entity, $now),
            'chart' => [
                'weekly' => $this->cashFlowSeries($entity, 'weekly'),
                'monthly' => $this->cashFlowSeries($entity, 'monthly'),
            ],
            'category_expenses' => $this->categoryExpensesThisMonth($entity, $now),
            'recent_transactions' => $this->recentTransactions($entity),
            'business_overview' => null,
            'invoice_reminders' => [],
        ];

        if ($entity->isBusiness()) {
            $payload['business_overview'] = $this->businessOverview($entity);
            $payload['invoice_reminders'] = $this->invoiceReminders($entity);
        }

        return $payload;
    }

    /**
     * @return list<array{id: string, name: string, type: string, balance: string}>
     */
    public function accountBalances(Entity $entity): array
    {
        return Account::query()
            ->where('accounts.entity_id', $entity->id)
            ->where('accounts.is_active', true)
            ->leftJoin('transaction_entries', 'accounts.id', '=', 'transaction_entries.account_id')
            ->leftJoin('transactions', function ($join) use ($entity): void {
                $join->on('transaction_entries.transaction_id', '=', 'transactions.id')
                    ->where('transactions.entity_id', '=', $entity->id);
            })
            ->select('accounts.id', 'accounts.name', 'accounts.type')
            ->selectRaw('COALESCE(SUM(transaction_entries.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(transaction_entries.kredit), 0) as total_kredit')
            ->groupBy('accounts.id', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.type')
            ->orderBy('accounts.name')
            ->get()
            ->map(function ($row) {
                $balance = $this->computeBalance(
                    $row->type,
                    (float) $row->total_debit,
                    (float) $row->total_kredit,
                );

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                    'balance' => number_format($balance, 2, '.', ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{total_assets: string, income_this_month: string, expense_this_month: string, net_this_month: string}
     */
    public function monthlyTotals(Entity $entity, ?Carbon $reference = null): array
    {
        $reference ??= Carbon::now();
        $start = $reference->copy()->startOfMonth()->toDateString();
        $end = $reference->copy()->endOfMonth()->toDateString();

        $accounts = $this->accountBalances($entity);
        $totalAssets = collect($accounts)
            ->where('type', 'asset')
            ->sum(fn (array $account) => (float) $account['balance']);

        $income = $this->sumTransactionsByType($entity, 'income', $start, $end);
        $expense = $this->sumTransactionsByType($entity, 'expense', $start, $end);

        return [
            'total_assets' => number_format($totalAssets, 2, '.', ''),
            'income_this_month' => number_format($income, 2, '.', ''),
            'expense_this_month' => number_format($expense, 2, '.', ''),
            'net_this_month' => number_format($income - $expense, 2, '.', ''),
        ];
    }

    /**
     * @return list<array{label: string, income: string, expense: string}>
     */
    public function cashFlowSeries(Entity $entity, string $period): array
    {
        $buckets = $period === 'weekly'
            ? $this->weeklyBuckets()
            : $this->monthlyBuckets();

        if ($buckets->isEmpty()) {
            return [];
        }

        $start = $buckets->first()['start'];
        $end = $buckets->last()['end'];

        $transactions = Transaction::query()
            ->forEntity($entity)
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('date', [$start, $end])
            ->get(['date', 'type', 'amount']);

        return $buckets->map(function (array $bucket) use ($transactions) {
            $inBucket = $transactions->filter(function (Transaction $transaction) use ($bucket): bool {
                $date = $transaction->date->toDateString();

                return $date >= $bucket['start'] && $date <= $bucket['end'];
            });

            $income = $inBucket->where('type', 'income')->sum(fn (Transaction $t) => (float) $t->amount);
            $expense = $inBucket->where('type', 'expense')->sum(fn (Transaction $t) => (float) $t->amount);

            return [
                'label' => $bucket['label'],
                'income' => number_format($income, 2, '.', ''),
                'expense' => number_format($expense, 2, '.', ''),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{name: string, amount: string}>
     */
    public function categoryExpensesThisMonth(Entity $entity, ?Carbon $reference = null): array
    {
        $reference ??= Carbon::now();

        return Transaction::query()
            ->forEntity($entity)
            ->with('category:id,name')
            ->where('type', 'expense')
            ->whereBetween('date', [
                $reference->copy()->startOfMonth()->toDateString(),
                $reference->copy()->endOfMonth()->toDateString(),
            ])
            ->whereNotNull('category_id')
            ->get()
            ->groupBy('category_id')
            ->map(function (Collection $group) {
                $category = $group->first()->category;

                return [
                    'name' => $category?->name ?? 'Lainnya',
                    'amount' => number_format(
                        $group->sum(fn (Transaction $t) => (float) $t->amount),
                        2,
                        '.',
                        '',
                    ),
                ];
            })
            ->sortByDesc(fn (array $row) => (float) $row['amount'])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, date: string, description: string|null, type: string, amount: string}>
     */
    public function recentTransactions(Entity $entity, int $limit = 5): array
    {
        return Transaction::query()
            ->forEntity($entity)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'date', 'description', 'type', 'amount'])
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'date' => $transaction->date->toDateString(),
                'description' => $transaction->description,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
            ])
            ->all();
    }

    /**
     * @return array{
     *     active_projects: list<array{id: string, name: string, status: string, budget: string|null, client_name: string|null}>,
     *     receivables: list<array{
     *         id: string,
     *         invoice_number: string,
     *         client_name: string|null,
     *         total: string,
     *         due_date: string|null,
     *         status: string,
     *         is_overdue: bool
     *     }>
     * }
     */
    public function businessOverview(Entity $entity): array
    {
        $activeProjects = Project::query()
            ->forEntity($entity)
            ->active()
            ->with('client:id,name')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'status', 'budget', 'client_id'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'budget' => $project->budget,
                'client_name' => $project->client?->name,
            ])
            ->all();

        $receivables = Invoice::query()
            ->forEntity($entity)
            ->unpaid()
            ->with('client:id,name')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
                'is_overdue' => $invoice->isOverdue(),
            ])
            ->all();

        return [
            'active_projects' => $activeProjects,
            'receivables' => $receivables,
        ];
    }

    /**
     * Reminder invoice jatuh tempo / overdue untuk dashboard bisnis.
     *
     * @return list<array{
     *     id: string,
     *     invoice_number: string,
     *     client_name: string|null,
     *     total: string,
     *     due_date: string|null,
     *     days_until_due: int|null,
     *     severity: 'overdue'|'due_soon'
     * }>
     */
    public function invoiceReminders(Entity $entity, int $dueSoonDays = 7): array
    {
        $overdue = Invoice::query()
            ->forEntity($entity)
            ->overdue()
            ->with('client:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $dueSoon = Invoice::query()
            ->forEntity($entity)
            ->dueSoon($dueSoonDays)
            ->with('client:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return $overdue->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name' => $invoice->client?->name,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'days_until_due' => $invoice->daysUntilDue(),
            'severity' => 'overdue',
        ])->concat(
            $dueSoon->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
                'due_date' => $invoice->due_date?->toDateString(),
                'days_until_due' => $invoice->daysUntilDue(),
                'severity' => 'due_soon',
            ]),
        )->values()->all();
    }

    private function computeBalance(string $type, float $debit, float $kredit): float
    {
        return match ($type) {
            'asset', 'expense' => $debit - $kredit,
            'liability', 'equity', 'revenue' => $kredit - $debit,
            default => 0.0,
        };
    }

    private function sumTransactionsByType(Entity $entity, string $type, string $start, string $end): float
    {
        return (float) Transaction::query()
            ->forEntity($entity)
            ->where('type', $type)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');
    }

    /**
     * @return Collection<int, array{label: string, start: string, end: string}>
     */
    private function weeklyBuckets(): Collection
    {
        $buckets = collect();

        for ($i = 7; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            $buckets->push([
                'label' => $start->format('d M'),
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]);
        }

        return $buckets;
    }

    /**
     * @return Collection<int, array{label: string, start: string, end: string}>
     */
    private function monthlyBuckets(): Collection
    {
        $buckets = collect();

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);

            $buckets->push([
                'label' => $month->translatedFormat('M Y'),
                'start' => $month->copy()->startOfMonth()->toDateString(),
                'end' => $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        return $buckets;
    }
}
