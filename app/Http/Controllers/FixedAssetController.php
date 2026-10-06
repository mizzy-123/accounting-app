<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisposeFixedAssetRequest;
use App\Http\Requests\PostDepreciationRequest;
use App\Http\Requests\StoreFixedAssetRequest;
use App\Http\Requests\UpdateFixedAssetRequest;
use App\Models\Account;
use App\Models\FixedAsset;
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

        $assets = FixedAsset::query()
            ->forEntity($entity)
            ->with(['assetAccount:id,name', 'accumulatedAccount:id,name', 'expenseAccount:id,name'])
            ->withCount('depreciationEntries')
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
                'asset_account' => $asset->assetAccount?->name,
                'accumulated_account' => $asset->accumulatedAccount?->name,
                'expense_account' => $asset->expenseAccount?->name,
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

        $posted = $this->depreciation->postThrough(
            $fixedAsset,
            $request->user(),
            $request->validated('through'),
        );

        $message = $posted === 0
            ? 'Tidak ada periode baru yang perlu dicatat.'
            : "{$posted} periode penyusutan berhasil dicatat.";

        return back()->with('success', $message);
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
}
