<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\TransactionPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionApprovalController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewApprovals', [Transaction::class, $entity]);

        $transactions = Transaction::query()
            ->forEntity($entity)
            ->pendingApproval()
            ->with(['category:id,name,type', 'creator:id,name'])
            ->orderBy('date')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Transaction $transaction) => TransactionPresenter::summary($transaction));

        return Inertia::render('approvals/index', [
            'transactions' => $transactions,
        ]);
    }

    public function submit(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->ensureActiveEntity($request, $transaction);
        $this->authorize('submit', $transaction);

        $this->transactionService->submitForApproval($transaction);

        return back()->with('success', 'Transaksi diajukan untuk approval.');
    }

    public function approve(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->ensureActiveEntity($request, $transaction);
        $this->authorize('approve', $transaction);

        $this->transactionService->approve($transaction, $request->user());

        return back()->with('success', 'Transaksi disetujui dan dikunci.');
    }

    public function reject(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->ensureActiveEntity($request, $transaction);
        $this->authorize('reject', $transaction);

        $this->transactionService->reject($transaction);

        return back()->with('success', 'Transaksi dikembalikan ke draft.');
    }

    private function ensureActiveEntity(Request $request, Transaction $transaction): void
    {
        $entity = $this->activeEntity($request);

        if ($transaction->entity_id !== $entity->id) {
            abort(404);
        }
    }
}
