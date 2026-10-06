<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAny', [Account::class, $entity]);

        $accounts = Account::query()
            ->forEntity($entity)
            ->withCount('entries')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'is_active' => $account->is_active,
                'is_system' => $account->is_system,
                'entries_count' => $account->entries_count,
            ]);

        return Inertia::render('accounts/index', [
            'accounts' => $accounts,
            'canManage' => $request->user()->can('create', [Account::class, $entity]),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $entity = $this->activeEntity($request);
        $this->authorize('create', [Account::class, $entity]);

        Account::create([
            'entity_id' => $entity->id,
            'name' => $request->validated('name'),
            'type' => $request->validated('type'),
            'is_active' => true,
            'is_system' => false,
        ]);

        return back()->with('success', 'Akun custom berhasil ditambahkan.');
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($account->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('update', $account);

        $validated = $request->validated();

        if ($account->is_system) {
            unset($validated['type'], $validated['name']);
        } elseif ($account->entries()->exists()) {
            unset($validated['type']);
        }

        $account->update($validated);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $entity = $this->activeEntity($request);

        if ($account->entity_id !== $entity->id) {
            abort(404);
        }

        $this->authorize('delete', $account);

        if ($account->is_system) {
            return back()->withErrors(['account' => 'Akun sistem tidak bisa dihapus.']);
        }

        if ($account->entries()->exists()) {
            return back()->withErrors([
                'account' => 'Akun tidak bisa dihapus karena sudah dipakai di jurnal.',
            ]);
        }

        $account->delete();

        return back()->with('success', 'Akun custom berhasil dihapus.');
    }
}
