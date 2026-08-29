<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Daftar semua clients untuk entity aktif.
     * Route: GET /clients
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Entity $entity */
        $entity = $request->attributes->get('active_entity');

        $this->authorize('viewAny', [Client::class, $entity]);

        $clients = Client::forEntity($entity)
            ->withCount('projects')
            ->withCount('transactions')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'contact_info' => $c->contact_info,
                'projects_count' => $c->projects_count,
                'transactions_count' => $c->transactions_count,
            ]);

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'canManage' => $user->isOwnerOf($entity),
        ]);
    }

    /**
     * Simpan client baru.
     * Route: POST /clients
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        /** @var Entity $entity */
        $entity = $request->attributes->get('active_entity');

        $this->authorize('create', [Client::class, $entity]);

        Client::create([
            'entity_id' => $entity->id,
            'name' => $request->validated('name'),
            'contact_info' => $request->validated('contact_info'),
        ]);

        return back()->with('success', 'Client berhasil ditambahkan.');
    }

    /**
     * Update client.
     * Route: PATCH /clients/{client}
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return back()->with('success', 'Client berhasil diperbarui.');
    }

    /**
     * Hapus client — tolak jika masih punya project.
     * Route: DELETE /clients/{client}
     */
    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            return back()->withErrors([
                'client' => 'Client tidak bisa dihapus karena masih memiliki project.',
            ]);
        }

        $client->delete();

        return back()->with('success', 'Client berhasil dihapus.');
    }
}
