<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisposeFixedAssetRequest;
use App\Http\Requests\PostDepreciationRequest;
use App\Http\Requests\RecognizeFixedAssetRequest;
use App\Http\Requests\StoreFixedAssetRequest;
use App\Http\Requests\UpdateFixedAssetRequest;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\Transaction;
use App\Services\DepreciationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class FixedAssetController extends Controller
{
    public function __construct(private DepreciationService $depreciation) {}

    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [FixedAsset::class, $entity]);

        $recognizedIds = Transaction::query()
            ->forEntity($entity)
            ->where('reference', 'like', 'asset-acquisition:%')
            ->pluck('reference')
            ->map(fn (string $reference) => str($reference)->after('asset-acquisition:')->toString())
            ->all();

        $assets = FixedAsset::query()
            ->forEntity($entity)
            ->with(['assetAccount:id,name', 'accumulatedAccount:id,name', 'expenseAccount:id,name'])
            ->withCount('depreciationEntries')
            ->withMax('depreciationEntries', 'period')
            ->orderBy('acquisition_date', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn (FixedAsset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'cost' => $asset->cost,
                'residual_value' => $asset->residual_value,
                'useful_life_months' => $asset->useful_life_months,
                'monthly_amount' => $asset->monthlyAmount(),
                'accumulated' => $asset->accumulatedAmount(),
                'book_value' => $asset->bookValue(),
                'status' => $asset->status,
                'notes' => $asset->notes,
                'periods_posted' => $asset->depreciation_entries_count,
                'last_posted_period' => $asset->depreciation_entries_max_period
                    ? Carbon::parse($asset->depreciation_entries_max_period)->format('Y-m')
                    : null,
                'asset_account' => $asset->assetAccount?->name,
                'accumulated_account' => $asset->accumulatedAccount?->name,
                'expense_account' => $asset->expenseAccount?->name,
                'acquisition_posted' => in_array($asset->id, $recognizedIds, true),
            ]);

        $accounts = Account::query()
            ->forEntity($entity)
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('assets/index', [
            'assets' => $assets,
            'accounts' => $accounts,
            'defaultPeriod' => Carbon::now()->format('Y-m'),
            'canManage' => $request->user()->can('create', [FixedAsset::class, $entity]),
        ]);
    }

    public function store(StoreFixedAssetRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [FixedAsset::class, $entity]);

        $this->depreciation->create($entity, $request->user(), $request->validated());

        return back()->with('success', 'Aset tetap berhasil ditambahkan.');
    }

    public function update(UpdateFixedAssetRequest $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($fixedAsset->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('update', $fixedAsset);

        $fixedAsset->update($request->validated());

        return back()->with('success', 'Aset tetap diperbarui.');
    }

    public function destroy(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($fixedAsset->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('delete', $fixedAsset);

        if ($fixedAsset->depreciationEntries()->exists()) {
            return back()->withErrors([
                'asset' => 'Aset tidak bisa dihapus karena sudah punya jurnal penyusutan.',
            ]);
        }

        $fixedAsset->delete();

        return back()->with('success', 'Aset tetap dihapus.');
    }

    public function depreciate(PostDepreciationRequest $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($fixedAsset->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('depreciate', $fixedAsset);

        $through = $request->validated('through');

        $posted = $this->depreciation->postThrough(
            $fixedAsset,
            $request->user(),
            $through,
        );

        if ($posted === 0) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'Penyusutan sampai '.$this->periodLabel($through).' sudah tercatat. Pilih bulan yang lebih baru untuk mencatat periode berikutnya.',
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "{$posted} periode penyusutan berhasil dicatat.",
            ]);
        }

        return back();
    }

    public function dispose(DisposeFixedAssetRequest $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($fixedAsset->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('dispose', $fixedAsset);

        try {
            $this->depreciation->dispose($fixedAsset, $request->user(), $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['asset' => $exception->getMessage()]);
        }

        return back()->with('success', 'Aset tetap berhasil dilepas.');
    }

    public function recognize(RecognizeFixedAssetRequest $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($fixedAsset->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('recognize', $fixedAsset);

        try {
            $this->depreciation->recognize(
                $fixedAsset,
                $request->user(),
                $request->validated('payment_account_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['asset' => $exception->getMessage()]);
        }

        return back()->with('success', 'Jurnal perolehan aset berhasil dicatat.');
    }

    private function periodLabel(string $yearMonth): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $date = Carbon::createFromFormat('!Y-m', $yearMonth);

        return $months[(int) $date->month].' '.$date->year;
    }
}
