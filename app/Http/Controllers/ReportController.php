<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportReportRequest;
use App\Models\Entity;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private ReportExportService $exports,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('view', $entity);

        $defaults = $this->defaultFilters();
        $type = $request->string('type')->toString() ?: $this->defaultType($entity);

        $allowed = $this->allowedTypes($entity);
        if (! in_array($type, $allowed, true)) {
            $type = $allowed[0];
        }

        $start = $request->string('start')->toString() ?: $defaults['start'];
        $end = $request->string('end')->toString() ?: $defaults['end'];
        $asOf = $request->string('as_of')->toString() ?: $defaults['as_of'];
        $period = $request->string('period')->toString() ?: $defaults['period'];

        $report = match ($type) {
            'cash_flow' => $this->reports->cashFlow(
                $entity,
                Carbon::parse($start),
                Carbon::parse($end),
            ),
            'profit_loss' => $this->reports->profitAndLoss(
                $entity,
                Carbon::parse($start),
                Carbon::parse($end),
            ),
            'balance_sheet' => $this->reports->balanceSheet(
                $entity,
                Carbon::parse($asOf),
            ),
            'project_profitability' => [
                'period' => ['start' => $start, 'end' => $end],
                'projects' => $this->reports->projectProfitability(
                    $entity,
                    Carbon::parse($start),
                    Carbon::parse($end),
                ),
            ],
            'budget' => $this->reports->budgetVsActual($entity, $period),
            default => [],
        };

        return Inertia::render('reports/index', [
            'entityType' => $entity->type,
            'type' => $type,
            'allowedTypes' => $allowed,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'as_of' => $asOf,
                'period' => $period,
            ],
            'report' => $report,
        ]);
    }

    public function export(ExportReportRequest $request): Response|StreamedResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('view', $entity);

        $validated = $request->validated();
        $type = $validated['type'];

        if (! in_array($type, $this->allowedTypes($entity), true)) {
            abort(403, 'Laporan ini tidak tersedia untuk entity aktif.');
        }

        $filters = [
            'start' => $validated['start'] ?? null,
            'end' => $validated['end'] ?? null,
            'as_of' => $validated['as_of'] ?? null,
            'period' => $validated['period'] ?? null,
        ];

        if ($validated['format'] === 'pdf') {
            return $this->exports->downloadPdf($entity, $type, $filters);
        }

        return $this->exports->downloadExcel($entity, $type, $filters);
    }

    /**
     * @return list<string>
     */
    private function allowedTypes(Entity $entity): array
    {
        if ($entity->isPersonal()) {
            return ['cash_flow', 'budget'];
        }

        return ['cash_flow', 'profit_loss', 'balance_sheet', 'project_profitability'];
    }

    private function defaultType(Entity $entity): string
    {
        return $entity->isPersonal() ? 'cash_flow' : 'profit_loss';
    }

    /**
     * @return array{start: string, end: string, as_of: string, period: string}
     */
    private function defaultFilters(): array
    {
        $now = Carbon::now();

        return [
            'start' => $now->copy()->startOfYear()->toDateString(),
            'end' => $now->copy()->endOfMonth()->toDateString(),
            'as_of' => $now->toDateString(),
            'period' => $now->format('Y-m'),
        ];
    }
}
