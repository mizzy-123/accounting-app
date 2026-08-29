<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceStatusRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [Invoice::class, $entity]);

        $status = $request->string('status')->toString();

        $invoices = Invoice::query()
            ->forEntity($entity)
            ->with(['project:id,name', 'client:id,name'])
            ->when(
                in_array($status, ['draft', 'sent', 'paid'], true),
                fn ($q) => $q->where('status', $status),
            )
            ->orderByDesc('issued_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invoice $invoice) => $this->present($invoice));

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => ['status' => $status ?: null],
            'canCreate' => $request->user()->can('create', [Invoice::class, $entity]),
            'canManageStatus' => $request->user()->isOwnerOf($entity),
        ]);
    }

    public function receivables(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [Invoice::class, $entity]);

        $invoices = Invoice::query()
            ->forEntity($entity)
            ->unpaid()
            ->with(['project:id,name', 'client:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('issued_date')
            ->get()
            ->map(fn (Invoice $invoice) => $this->present($invoice));

        $totalOutstanding = $invoices->sum(fn (array $row) => (float) $row['total']);
        $overdueCount = $invoices->filter(fn (array $row) => $row['is_overdue'])->count();

        return Inertia::render('invoices/receivables', [
            'invoices' => $invoices,
            'summary' => [
                'count' => $invoices->count(),
                'total_outstanding' => number_format($totalOutstanding, 2, '.', ''),
                'overdue_count' => $overdueCount,
            ],
            'canManageStatus' => $request->user()->isOwnerOf($entity),
        ]);
    }

    public function create(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Invoice::class, $entity]);

        $projects = Project::query()
            ->forEntity($entity)
            ->with('client:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'client_id', 'status'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'client_id' => $project->client_id,
                'client_name' => $project->client?->name,
                'status' => $project->status,
            ]);

        $clients = Client::query()
            ->forEntity($entity)
            ->orderBy('name')
            ->get(['id', 'name']);

        $prefillProjectId = $request->string('project_id')->toString() ?: null;

        return Inertia::render('invoices/create', [
            'projects' => $projects,
            'clients' => $clients,
            'prefillProjectId' => $prefillProjectId,
            'suggestedNumber' => Invoice::generateNumber($entity),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Invoice::class, $entity]);

        $validated = $request->validated();
        $project = Project::query()
            ->forEntity($entity)
            ->whereKey($validated['project_id'])
            ->firstOrFail();

        $items = collect($validated['items'])->map(function (array $item) {
            $qty = (float) $item['qty'];
            $price = (float) $item['price'];

            return [
                'name' => $item['name'],
                'qty' => $qty,
                'price' => $price,
                'subtotal' => round($qty * $price, 2),
            ];
        })->values()->all();

        $subtotal = collect($items)->sum('subtotal');
        $discount = (float) ($validated['discount'] ?? 0);
        $total = max(0, round($subtotal - $discount, 2));

        $clientId = $validated['client_id'] ?? $project->client_id;

        $invoice = Invoice::create([
            'entity_id' => $entity->id,
            'project_id' => $project->id,
            'client_id' => $clientId,
            'created_by' => $request->user()->id,
            'invoice_number' => Invoice::generateNumber($entity),
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'issued_date' => $validated['issued_date'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice berhasil dibuat.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $this->ensureSameEntity($request, $invoice);

        $invoice->load(['project:id,name', 'client:id,name', 'creator:id,name']);

        return Inertia::render('invoices/show', [
            'invoice' => $this->present($invoice, detailed: true),
            'canManageStatus' => $request->user()->can('updateStatus', $invoice),
            'canDelete' => $request->user()->can('delete', $invoice),
        ]);
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('updateStatus', $invoice);
        $this->ensureSameEntity($request, $invoice);

        $status = $request->validated('status');
        $previous = $invoice->status;

        // Transisi sederhana: draft → sent → paid (boleh mundur ke sent dari paid oleh owner)
        if ($previous === 'paid' && $status === 'draft') {
            return back()->withErrors([
                'status' => 'Invoice yang sudah paid tidak bisa dikembalikan ke draft.',
            ]);
        }

        $invoice->status = $status;
        $invoice->paid_at = $status === 'paid' ? now() : null;
        $invoice->save();

        return back()->with('success', "Status invoice diubah menjadi {$status}.");
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);
        $this->ensureSameEntity($request, $invoice);

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice draft berhasil dihapus.');
    }

    private function ensureSameEntity(Request $request, Invoice $invoice): void
    {
        $entity = $this->activeEntity($request);

        if ($invoice->entity_id !== $entity->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $invoice, bool $detailed = false): array
    {
        $base = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'total' => $invoice->total,
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount,
            'issued_date' => $invoice->issued_date->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'is_overdue' => $invoice->isOverdue(),
            'days_until_due' => $invoice->daysUntilDue(),
            'project' => $invoice->project ? [
                'id' => $invoice->project->id,
                'name' => $invoice->project->name,
            ] : null,
            'client' => $invoice->client ? [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
            ] : null,
        ];

        if (! $detailed) {
            return $base;
        }

        return [
            ...$base,
            'notes' => $invoice->notes,
            'items' => $invoice->items,
            'creator' => $invoice->creator ? [
                'id' => $invoice->creator->id,
                'name' => $invoice->creator->name,
            ] : null,
        ];
    }
}
