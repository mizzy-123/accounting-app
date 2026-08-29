<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterEntityTransferRequest;
use App\Http\Requests\StoreSimpleTransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Client;
use App\Models\Entity;
use App\Models\Project;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\TransactionPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [Transaction::class, $entity]);

        $transactions = Transaction::query()
            ->forEntity($entity)
            ->with(['category:id,name,type', 'creator:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (Transaction $transaction) => TransactionPresenter::summary($transaction));

        return Inertia::render('transactions/index', [
            'transactions' => $transactions,
            'canCreate' => $request->user()->can('create', [Transaction::class, $entity]),
        ]);
    }

    public function create(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Transaction::class, $entity]);

        return Inertia::render('transactions/create', $this->formOptions($request, $entity));
    }

    public function store(StoreSimpleTransactionRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $user = $request->user();
        $validated = $request->validated();

        $transaction = match ($validated['type']) {
            'income' => $this->transactionService->createIncome($entity, $user, $validated),
            'expense' => $this->transactionService->createExpense($entity, $user, $validated),
            'transfer' => $this->transactionService->createTransfer($entity, $user, $validated),
        };

        if ($request->hasFile('attachment')) {
            $this->transactionService->attachFile($transaction, $request->file('attachment'));
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil dicatat.');
    }

    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $entity = $this->activeEntity($request);
        if ($transaction->entity_id !== $entity->id) {
            abort(404);
        }

        $transaction->load(['entries.account:id,name,type', 'category', 'creator:id,name', 'attachments']);

        return Inertia::render('transactions/show', [
            'transaction' => TransactionPresenter::detail($transaction),
        ]);
    }

    public function storeInterEntity(StoreInterEntityTransferRequest $request): RedirectResponse
    {
        $fromEntity = $this->activeEntity($request);
        $toEntity = Entity::findOrFail($request->validated('to_entity_id'));

        if (! $request->user()->isOwnerOf($toEntity)) {
            abort(403, 'Anda harus menjadi owner di entity tujuan.');
        }

        [$outgoing] = $this->transactionService->createInterEntityTransfer(
            $fromEntity,
            $toEntity,
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('transactions.show', $outgoing)
            ->with('success', 'Transfer antar entity berhasil dicatat.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request, Entity $entity): array
    {
        $assetAccounts = Account::query()
            ->forEntity($entity)
            ->active()
            ->where('type', 'asset')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $paymentAccounts = Account::query()
            ->forEntity($entity)
            ->active()
            ->whereIn('type', ['asset', 'liability'])
            ->orderByRaw("CASE type WHEN 'asset' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $transferDestinationAccounts = $paymentAccounts;

        $chartOverview = Account::query()
            ->forEntity($entity)
            ->active()
            ->orderByRaw("CASE type WHEN 'asset' THEN 0 WHEN 'liability' THEN 1 WHEN 'equity' THEN 2 WHEN 'revenue' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->groupBy('type')
            ->map(fn ($group) => $group->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
            ])->values())
            ->all();

        $incomeCategories = Category::query()
            ->forEntity($entity)
            ->where('type', 'income')
            ->orderBy('name')
            ->get(['id', 'name']);

        $expenseCategories = Category::query()
            ->forEntity($entity)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get(['id', 'name']);

        $otherEntities = $request->user()
            ->entities()
            ->where('entities.id', '!=', $entity->id)
            ->wherePivot('role', 'owner')
            ->get(['entities.id', 'entities.name', 'entities.type'])
            ->map(fn (Entity $otherEntity) => [
                'id' => $otherEntity->id,
                'name' => $otherEntity->name,
                'type' => $otherEntity->type,
                'accounts' => Account::query()
                    ->forEntity($otherEntity)
                    ->active()
                    ->where('type', 'asset')
                    ->orderBy('name')
                    ->get(['id', 'name', 'type']),
            ]);

        // Untuk entity bisnis: kirim projects + clients ke form
        $projects = [];
        $clients = [];
        if ($entity->isBusiness()) {
            $projects = Project::forEntity($entity)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'client_id']);

            $clients = Client::forEntity($entity)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return [
            'accounts' => $assetAccounts,
            'paymentAccounts' => $paymentAccounts,
            'transferDestinationAccounts' => $transferDestinationAccounts,
            'chartOverview' => $chartOverview,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'canInterEntityTransfer' => $request->user()->can('createInterEntityTransfer', Transaction::class)
                && $otherEntities->isNotEmpty(),
            'otherEntities' => $otherEntities,
            'projects' => $projects,
            'clients' => $clients,
            'isBusiness' => $entity->isBusiness(),
        ];
    }
}
