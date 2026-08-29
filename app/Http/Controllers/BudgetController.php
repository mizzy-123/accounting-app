<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [Budget::class, $entity]);

        $period = $request->string('period')->toString()
            ?: Carbon::now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = Carbon::now()->format('Y-m');
        }

        $comparison = $this->reports->budgetVsActual($entity, $period);

        $budgets = Budget::query()
            ->forEntity($entity)
            ->forPeriod($period)
            ->with('category:id,name,type')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name,
                'period' => $budget->period,
                'amount' => $budget->amount,
            ]);

        $categories = Category::query()
            ->forEntity($entity)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('budgets/index', [
            'period' => $period,
            'budgets' => $budgets,
            'comparison' => $comparison,
            'categories' => $categories,
            'canManage' => $request->user()->can('create', [Budget::class, $entity]),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Budget::class, $entity]);

        $validated = $request->validated();

        Budget::query()->updateOrCreate(
            [
                'entity_id' => $entity->id,
                'category_id' => $validated['category_id'],
                'period' => $validated['period'],
            ],
            ['amount' => $validated['amount']],
        );

        return back()->with('success', 'Budget disimpan.');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($budget->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('update', $budget);

        $budget->update(['amount' => $request->validated('amount')]);

        return back()->with('success', 'Budget diperbarui.');
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($budget->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('delete', $budget);
        $budget->delete();

        return back()->with('success', 'Budget dihapus.');
    }
}
