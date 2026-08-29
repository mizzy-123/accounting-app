<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualJournalRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManualJournalController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function create(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('createAdjustment', [Transaction::class, $entity]);

        $accounts = Account::query()
            ->forEntity($entity)
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('journals/create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(StoreManualJournalRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        $transaction = $this->transactionService->createAdjustment(
            $entity,
            $request->user(),
            $request->validated(),
        );

        if ($request->hasFile('attachment')) {
            $this->transactionService->attachFile($transaction, $request->file('attachment'));
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Jurnal penyesuaian berhasil dicatat.');
    }
}
