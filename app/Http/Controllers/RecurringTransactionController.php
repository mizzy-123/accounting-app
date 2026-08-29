<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;
use App\Models\RecurringTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [RecurringTransaction::class, $entity]);

        $recurringTransactions = RecurringTransaction::query()
            ->where('entity_id', $entity->id)
            ->orderBy('next_run_at')
            ->get()
            ->map(fn (RecurringTransaction $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'type' => $item->template['type'] ?? null,
                'amount' => $item->template['amount'] ?? null,
                'frequency' => $item->frequency,
                'next_run_at' => $item->next_run_at->toDateString(),
                'ends_at' => $item->ends_at?->toDateString(),
                'is_active' => $item->is_active,
            ]);

        return Inertia::render('recurring/index', [
            'recurringTransactions' => $recurringTransactions,
            'canCreate' => $request->user()->can('create', [RecurringTransaction::class, $entity]),
            'formOptions' => $this->formOptions($entity),
        ]);
    }

    public function store(StoreRecurringTransactionRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $validated = $request->validated();

        $template = match ($validated['type']) {
            'income', 'expense' => [
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'account_id' => $validated['account_id'],
                'category_id' => $validated['category_id'],
            ],
            'transfer' => [
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'from_account_id' => $validated['from_account_id'],
                'to_account_id' => $validated['to_account_id'],
            ],
        };

        RecurringTransaction::create([
            'entity_id' => $entity->id,
            'created_by' => $request->user()->id,
            'description' => $validated['description'],
            'template' => $template,
            'frequency' => $validated['frequency'],
            'next_run_at' => $validated['next_run_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Transaksi berulang berhasil disimpan.');
    }

    public function destroy(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('delete', $recurringTransaction);

        $entity = $this->activeEntity($request);
        if ($recurringTransaction->entity_id !== $entity->id) {
            abort(404);
        }

        $recurringTransaction->update(['is_active' => false]);

        return back()->with('success', 'Transaksi berulang dinonaktifkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Entity $entity): array
    {
        return [
            'accounts' => Account::query()
                ->forEntity($entity)
                ->active()
                ->where('type', 'asset')
                ->orderBy('name')
                ->get(['id', 'name']),
            'incomeCategories' => Category::query()
                ->forEntity($entity)
                ->where('type', 'income')
                ->orderBy('name')
                ->get(['id', 'name']),
            'expenseCategories' => Category::query()
                ->forEntity($entity)
                ->where('type', 'expense')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
