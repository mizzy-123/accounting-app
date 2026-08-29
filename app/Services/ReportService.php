<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Entity;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Cash flow bulanan + breakdown kategori untuk rentang tanggal.
     *
     * @return array{
     *     period: array{start: string, end: string},
     *     monthly: list<array{label: string, period: string, income: string, expense: string, net: string}>,
     *     categories: list<array{name: string, type: string, amount: string}>,
     *     totals: array{income: string, expense: string, net: string}
     * }
     */
    public function cashFlow(Entity $entity, Carbon $start, Carbon $end): array
    {
        $transactions = Transaction::query()
            ->forEntity($entity)
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('category:id,name,type')
            ->get(['id', 'date', 'type', 'amount', 'category_id']);

        $monthly = $this->monthlyBucketsBetween($start, $end)->map(function (array $bucket) use ($transactions) {
            $inBucket = $transactions->filter(function (Transaction $tx) use ($bucket): bool {
                $date = $tx->date->toDateString();

                return $date >= $bucket['start'] && $date <= $bucket['end'];
            });

            $income = $inBucket->where('type', 'income')->sum(fn (Transaction $t) => (float) $t->amount);
            $expense = $inBucket->where('type', 'expense')->sum(fn (Transaction $t) => (float) $t->amount);

            return [
                'label' => $bucket['label'],
                'period' => $bucket['period'],
                'income' => $this->money($income),
                'expense' => $this->money($expense),
                'net' => $this->money($income - $expense),
            ];
        })->values()->all();

        $categories = $transactions
            ->groupBy(fn (Transaction $tx) => ($tx->category_id ?? 'none').'|'.$tx->type)
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => $first->category?->name ?? 'Tanpa kategori',
                    'type' => $first->type,
                    'amount' => $this->money(
                        $group->sum(fn (Transaction $t) => (float) $t->amount),
                    ),
                ];
            })
            ->sortByDesc(fn (array $row) => (float) $row['amount'])
            ->values()
            ->all();

        $totalIncome = $transactions->where('type', 'income')->sum(fn (Transaction $t) => (float) $t->amount);
        $totalExpense = $transactions->where('type', 'expense')->sum(fn (Transaction $t) => (float) $t->amount);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'monthly' => $monthly,
            'categories' => $categories,
            'totals' => [
                'income' => $this->money($totalIncome),
                'expense' => $this->money($totalExpense),
                'net' => $this->money($totalIncome - $totalExpense),
            ],
        ];
    }

    /**
     * Laba-rugi dari saldo akun revenue/expense dalam periode.
     *
     * @return array{
     *     period: array{start: string, end: string},
     *     revenue: list<array{id: string, name: string, amount: string}>,
     *     expenses: list<array{id: string, name: string, amount: string}>,
     *     totals: array{revenue: string, expenses: string, net_income: string}
     * }
     */
    public function profitAndLoss(Entity $entity, Carbon $start, Carbon $end): array
    {
        $balances = $this->accountBalancesInPeriod($entity, $start, $end);

        $revenue = $balances->where('type', 'revenue')
            ->filter(fn (array $row) => abs((float) $row['balance']) > 0.00001)
            ->map(fn (array $row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'amount' => $row['balance'],
            ])
            ->values()
            ->all();

        $expenses = $balances->where('type', 'expense')
            ->filter(fn (array $row) => abs((float) $row['balance']) > 0.00001)
            ->map(fn (array $row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'amount' => $row['balance'],
            ])
            ->values()
            ->all();

        $totalRevenue = collect($revenue)->sum(fn (array $r) => (float) $r['amount']);
        $totalExpenses = collect($expenses)->sum(fn (array $r) => (float) $r['amount']);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totals' => [
                'revenue' => $this->money($totalRevenue),
                'expenses' => $this->money($totalExpenses),
                'net_income' => $this->money($totalRevenue - $totalExpenses),
            ],
        ];
    }

    /**
     * Neraca: aset = liability + equity (termasuk laba berjalan).
     *
     * @return array{
     *     as_of: string,
     *     assets: list<array{id: string, name: string, amount: string}>,
     *     liabilities: list<array{id: string, name: string, amount: string}>,
     *     equity: list<array{id: string, name: string, amount: string}>,
     *     totals: array{
     *         assets: string,
     *         liabilities: string,
     *         equity: string,
     *         liabilities_and_equity: string,
     *         is_balanced: bool,
     *         difference: string
     *     }
     * }
     */
    public function balanceSheet(Entity $entity, Carbon $asOf): array
    {
        $balances = $this->accountBalancesAsOf($entity, $asOf);

        $assets = $this->sectionFromBalances($balances, 'asset');
        $liabilities = $this->sectionFromBalances($balances, 'liability');
        $equityAccounts = $this->sectionFromBalances($balances, 'equity');

        $totalRevenue = $balances->where('type', 'revenue')->sum(fn (array $r) => (float) $r['balance']);
        $totalExpense = $balances->where('type', 'expense')->sum(fn (array $r) => (float) $r['balance']);
        $netIncome = $totalRevenue - $totalExpense;

        $equity = $equityAccounts;
        if (abs($netIncome) > 0.00001) {
            $equity[] = [
                'id' => 'retained-earnings',
                'name' => 'Laba/(Rugi) Berjalan',
                'amount' => $this->money($netIncome),
            ];
        }

        $totalAssets = collect($assets)->sum(fn (array $r) => (float) $r['amount']);
        $totalLiabilities = collect($liabilities)->sum(fn (array $r) => (float) $r['amount']);
        $totalEquity = collect($equity)->sum(fn (array $r) => (float) $r['amount']);
        $liabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $difference = round($totalAssets - $liabilitiesAndEquity, 2);

        return [
            'as_of' => $asOf->toDateString(),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'assets' => $this->money($totalAssets),
                'liabilities' => $this->money($totalLiabilities),
                'equity' => $this->money($totalEquity),
                'liabilities_and_equity' => $this->money($liabilitiesAndEquity),
                'is_balanced' => abs($difference) < 0.01,
                'difference' => $this->money($difference),
            ],
        ];
    }

    /**
     * Profitabilitas per project (revenue vs cost dari transaksi tagged).
     *
     * @return list<array{
     *     id: string,
     *     name: string,
     *     client_name: string|null,
     *     status: string,
     *     budget: string|null,
     *     revenue: string,
     *     cost: string,
     *     profit: string,
     *     margin_percent: string|null
     * }>
     */
    public function projectProfitability(Entity $entity, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $query = Transaction::query()
            ->forEntity($entity)
            ->whereNotNull('project_id')
            ->whereIn('type', ['income', 'expense']);

        if ($start && $end) {
            $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        }

        $grouped = $query->get(['project_id', 'type', 'amount'])
            ->groupBy('project_id');

        $projects = Project::query()
            ->forEntity($entity)
            ->with('client:id,name')
            ->orderBy('name')
            ->get();

        return $projects->map(function (Project $project) use ($grouped) {
            $txs = $grouped->get($project->id, collect());
            $revenue = $txs->where('type', 'income')->sum(fn (Transaction $t) => (float) $t->amount);
            $cost = $txs->where('type', 'expense')->sum(fn (Transaction $t) => (float) $t->amount);
            $profit = $revenue - $cost;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : null;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client?->name,
                'status' => $project->status,
                'budget' => $project->budget,
                'revenue' => $this->money($revenue),
                'cost' => $this->money($cost),
                'profit' => $this->money($profit),
                'margin_percent' => $margin !== null
                    ? number_format($margin, 1, '.', '')
                    : null,
            ];
        })->values()->all();
    }

    /**
     * Budget vs actual per kategori untuk periode YYYY-MM.
     *
     * @return array{
     *     period: string,
     *     items: list<array{
     *         budget_id: string|null,
     *         category_id: string,
     *         category_name: string,
     *         budget: string,
     *         actual: string,
     *         remaining: string,
     *         progress_percent: string
     *     }>,
     *     totals: array{budget: string, actual: string, remaining: string}
     * }
     */
    public function budgetVsActual(Entity $entity, string $period): array
    {
        $month = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $budgets = Budget::query()
            ->forEntity($entity)
            ->forPeriod($period)
            ->with('category:id,name,type')
            ->get();

        $actualByCategory = Transaction::query()
            ->forEntity($entity)
            ->where('type', 'expense')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('category_id')
            ->get(['category_id', 'amount'])
            ->groupBy('category_id')
            ->map(fn (Collection $group) => $group->sum(fn (Transaction $t) => (float) $t->amount));

        $items = $budgets->map(function (Budget $budget) use ($actualByCategory) {
            $actual = (float) ($actualByCategory->get($budget->category_id) ?? 0);
            $limit = (float) $budget->amount;
            $remaining = $limit - $actual;
            $progress = $limit > 0 ? min(($actual / $limit) * 100, 999) : 0;

            return [
                'budget_id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name ?? 'Kategori',
                'budget' => $this->money($limit),
                'actual' => $this->money($actual),
                'remaining' => $this->money($remaining),
                'progress_percent' => number_format($progress, 1, '.', ''),
            ];
        })->values()->all();

        $totalBudget = collect($items)->sum(fn (array $i) => (float) $i['budget']);
        $totalActual = collect($items)->sum(fn (array $i) => (float) $i['actual']);

        return [
            'period' => $period,
            'items' => $items,
            'totals' => [
                'budget' => $this->money($totalBudget),
                'actual' => $this->money($totalActual),
                'remaining' => $this->money($totalBudget - $totalActual),
            ],
        ];
    }

    /**
     * @return Collection<int, array{id: string, name: string, type: string, balance: string}>
     */
    public function accountBalancesAsOf(Entity $entity, Carbon $asOf): Collection
    {
        return $this->queryAccountBalances($entity, null, $asOf);
    }

    /**
     * @return Collection<int, array{id: string, name: string, type: string, balance: string}>
     */
    public function accountBalancesInPeriod(Entity $entity, Carbon $start, Carbon $end): Collection
    {
        return $this->queryAccountBalances($entity, $start, $end);
    }

    /**
     * @return Collection<int, array{id: string, name: string, type: string, balance: string}>
     */
    private function queryAccountBalances(Entity $entity, ?Carbon $start, Carbon $end): Collection
    {
        $query = Account::query()
            ->where('accounts.entity_id', $entity->id)
            ->leftJoin('transaction_entries', 'accounts.id', '=', 'transaction_entries.account_id')
            ->leftJoin('transactions', function ($join) use ($entity, $start, $end): void {
                $join->on('transaction_entries.transaction_id', '=', 'transactions.id')
                    ->where('transactions.entity_id', '=', $entity->id)
                    ->where('transactions.date', '<=', $end->toDateString());

                if ($start) {
                    $join->where('transactions.date', '>=', $start->toDateString());
                }
            })
            ->select('accounts.id', 'accounts.name', 'accounts.type')
            ->selectRaw('COALESCE(SUM(transaction_entries.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(transaction_entries.kredit), 0) as total_kredit')
            ->groupBy('accounts.id', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.name');

        return $query->get()->map(function ($row) {
            $balance = $this->computeBalance(
                $row->type,
                (float) $row->total_debit,
                (float) $row->total_kredit,
            );

            return [
                'id' => $row->id,
                'name' => $row->name,
                'type' => $row->type,
                'balance' => $this->money($balance),
            ];
        });
    }

    /**
     * @param  Collection<int, array{id: string, name: string, type: string, balance: string}>  $balances
     * @return list<array{id: string, name: string, amount: string}>
     */
    private function sectionFromBalances(Collection $balances, string $type): array
    {
        return $balances
            ->where('type', $type)
            ->filter(fn (array $row) => abs((float) $row['balance']) > 0.00001)
            ->map(fn (array $row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'amount' => $row['balance'],
            ])
            ->values()
            ->all();
    }

    private function computeBalance(string $type, float $debit, float $kredit): float
    {
        return match ($type) {
            'asset', 'expense' => $debit - $kredit,
            'liability', 'equity', 'revenue' => $kredit - $debit,
            default => 0.0,
        };
    }

    /**
     * @return Collection<int, array{label: string, period: string, start: string, end: string}>
     */
    private function monthlyBucketsBetween(Carbon $start, Carbon $end): Collection
    {
        $buckets = collect();
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $rangeStart = $monthStart->lt($start) ? $start->copy() : $monthStart;
            $rangeEnd = $monthEnd->gt($end) ? $end->copy() : $monthEnd;

            $buckets->push([
                'label' => $cursor->translatedFormat('M Y'),
                'period' => $cursor->format('Y-m'),
                'start' => $rangeStart->toDateString(),
                'end' => $rangeEnd->toDateString(),
            ]);

            $cursor->addMonth();
        }

        return $buckets;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
