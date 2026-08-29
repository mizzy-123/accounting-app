<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmBankImportRequest;
use App\Http\Requests\PreviewBankImportRequest;
use App\Http\Requests\StoreBankImportRuleRequest;
use App\Http\Requests\UpdateBankImportRuleRequest;
use App\Models\Account;
use App\Models\BankImportBatch;
use App\Models\BankImportRule;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankImportController extends Controller
{
    public function __construct(private ImportService $importService) {}

    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Transaction::class, $entity]);

        $accounts = Account::query()
            ->forEntity($entity)
            ->active()
            ->where('type', 'asset')
            ->orderBy('name')
            ->get(['id', 'name']);

        $batches = BankImportBatch::query()
            ->forEntity($entity)
            ->with(['importer:id,name', 'account:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (BankImportBatch $batch) => [
                'id' => $batch->id,
                'file_name' => $batch->file_name,
                'total_rows' => $batch->total_rows,
                'imported_rows' => $batch->imported_rows,
                'skipped_rows' => $batch->skipped_rows,
                'created_at' => $batch->created_at?->toIso8601String(),
                'importer' => $batch->importer?->name,
                'account' => $batch->account?->name,
            ]);

        $rulesCount = BankImportRule::query()->forEntity($entity)->count();

        return Inertia::render('import/index', [
            'accounts' => $accounts,
            'batches' => $batches,
            'rulesCount' => $rulesCount,
        ]);
    }

    public function preview(PreviewBankImportRequest $request): Response|RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Transaction::class, $entity]);

        $account = Account::query()
            ->forEntity($entity)
            ->active()
            ->whereKey($request->validated('account_id'))
            ->where('type', 'asset')
            ->first();

        if (! $account) {
            return back()->withErrors(['account_id' => 'Akun tidak valid untuk entity ini.']);
        }

        try {
            $rows = $this->importService->preview($entity, $request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $categories = Category::query()
            ->forEntity($entity)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('import/preview', [
            'account' => ['id' => $account->id, 'name' => $account->name],
            'fileName' => $request->file('file')->getClientOriginalName(),
            'rows' => $rows,
            'categories' => $categories,
        ]);
    }

    public function confirm(ConfirmBankImportRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Transaction::class, $entity]);

        $validated = $request->validated();

        $included = collect($validated['rows'])
            ->filter(fn (array $row) => ($row['include'] ?? true) === true);

        if ($included->isEmpty()) {
            return back()->withErrors(['rows' => 'Pilih minimal satu baris untuk diimpor.']);
        }

        // Pastikan kategori cocok dengan tipe baris
        $categories = Category::query()
            ->forEntity($entity)
            ->whereIn('id', $included->pluck('category_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($included as $index => $row) {
            $category = $categories->get($row['category_id']);
            if (! $category || $category->type !== $row['type']) {
                return back()->withErrors([
                    "rows.{$index}.category_id" => 'Kategori harus sesuai tipe baris (income/expense).',
                ]);
            }
        }

        $batch = $this->importService->confirm(
            $entity,
            $request->user(),
            $validated['account_id'],
            $validated['file_name'],
            $validated['rows'],
        );

        return redirect()
            ->route('import.index')
            ->with(
                'success',
                "Import selesai: {$batch->imported_rows} transaksi draft dibuat dari {$batch->file_name}.",
            );
    }

    public function rules(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [BankImportRule::class, $entity]);

        $rules = BankImportRule::query()
            ->forEntity($entity)
            ->with('category:id,name,type')
            ->orderBy('keyword')
            ->get()
            ->map(fn (BankImportRule $rule) => [
                'id' => $rule->id,
                'keyword' => $rule->keyword,
                'category' => [
                    'id' => $rule->category->id,
                    'name' => $rule->category->name,
                    'type' => $rule->category->type,
                ],
            ]);

        $categories = Category::query()
            ->forEntity($entity)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('import/rules', [
            'rules' => $rules,
            'categories' => $categories,
            'canManage' => $request->user()->can('create', [BankImportRule::class, $entity]),
        ]);
    }

    public function storeRule(StoreBankImportRuleRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [BankImportRule::class, $entity]);

        BankImportRule::create([
            'entity_id' => $entity->id,
            'keyword' => $request->validated('keyword'),
            'category_id' => $request->validated('category_id'),
        ]);

        return back()->with('success', 'Rule auto-kategori berhasil ditambahkan.');
    }

    public function updateRule(UpdateBankImportRuleRequest $request, BankImportRule $bankImportRule): RedirectResponse
    {
        $this->authorize('update', $bankImportRule);
        $this->ensureSameEntity($request, $bankImportRule->entity_id);

        $bankImportRule->update($request->validated());

        return back()->with('success', 'Rule berhasil diperbarui.');
    }

    public function destroyRule(Request $request, BankImportRule $bankImportRule): RedirectResponse
    {
        $this->authorize('delete', $bankImportRule);
        $this->ensureSameEntity($request, $bankImportRule->entity_id);

        $bankImportRule->delete();

        return back()->with('success', 'Rule berhasil dihapus.');
    }

    private function ensureSameEntity(Request $request, string $entityId): void
    {
        if ($this->activeEntity($request)->id !== $entityId) {
            abort(404);
        }
    }
}
