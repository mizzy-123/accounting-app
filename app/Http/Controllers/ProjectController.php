<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Entity;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Daftar semua projects entity aktif.
     * Route: GET /projects
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Entity $entity */
        $entity = $request->attributes->get('active_entity');

        $this->authorize('viewAny', [Project::class, $entity]);

        $statusFilter = $request->query('status', 'active');

        $projects = Project::forEntity($entity)
            ->when($statusFilter !== 'all', fn ($q) => $q->byStatus($statusFilter))
            ->with('client:id,name')
            ->withCount('transactions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'budget' => $p->budget,
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'client' => $p->client ? ['id' => $p->client->id, 'name' => $p->client->name] : null,
                'total_revenue' => $p->totalRevenue(),
                'total_expense' => $p->totalExpense(),
                'net_profit' => $p->netProfit(),
                'budget_used_percent' => $p->budgetUsedPercent(),
                'transactions_count' => $p->transactions_count,
            ]);

        // Clients untuk dropdown di form tambah project
        $clients = Client::forEntity($entity)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('projects/index', [
            'projects' => $projects,
            'clients' => $clients,
            'statusFilter' => $statusFilter,
            'canManage' => $user->isOwnerOf($entity) || in_array($user->getEntityRole($entity), ['owner', 'member']),
            'canDelete' => $user->isOwnerOf($entity),
        ]);
    }

    /**
     * Detail project + transaksi terkait.
     * Route: GET /projects/{project}
     */
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        /** @var User $user */
        $user = $request->user();

        $entity = $project->entity;

        // Transaksi yang ter-tag ke project ini
        $transactions = $project->transactions()
            ->with(['category:id,name', 'creator:id,name'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'date' => $t->date->toDateString(),
                'description' => $t->description,
                'type' => $t->type,
                'amount' => $t->amount,
                'status' => $t->status,
                'category' => $t->category ? ['name' => $t->category->name] : null,
                'creator' => ['name' => $t->creator->name],
            ]);

        return Inertia::render('projects/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'budget' => $project->budget,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'client' => $project->client ? ['id' => $project->client->id, 'name' => $project->client->name] : null,
                'total_revenue' => $project->totalRevenue(),
                'total_expense' => $project->totalExpense(),
                'net_profit' => $project->netProfit(),
                'budget_used_percent' => $project->budgetUsedPercent(),
                'is_over_budget' => $project->isOverBudget(),
            ],
            'transactions' => $transactions,
            'canManage' => in_array($user->getEntityRole($entity), ['owner', 'member']),
            'canDelete' => $user->isOwnerOf($entity),
        ]);
    }

    /**
     * Simpan project baru.
     * Route: POST /projects
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        /** @var Entity $entity */
        $entity = $request->attributes->get('active_entity');

        $this->authorize('create', [Project::class, $entity]);

        $project = Project::create([
            'entity_id' => $entity->id,
            ...$request->validated(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Project \"{$project->name}\" berhasil dibuat.");
    }

    /**
     * Update project.
     * Route: PATCH /projects/{project}
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return back()->with('success', 'Project berhasil diperbarui.');
    }

    /**
     * Hapus project — tolak jika masih ada transaksi linked.
     * Route: DELETE /projects/{project}
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        if ($project->transactions()->exists()) {
            return back()->withErrors([
                'project' => 'Project tidak bisa dihapus karena masih memiliki transaksi terkait.',
            ]);
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project berhasil dihapus.');
    }
}
