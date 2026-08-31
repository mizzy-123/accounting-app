<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntityRequest;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * Buat entity bisnis baru.
     * Route: POST /entities
     */
    public function store(StoreEntityRequest $request, EntityService $entityService): RedirectResponse
    {
        $this->authorize('create', Entity::class);

        $entity = $entityService->createEntity(
            $request->user(),
            $request->validated('name'),
            $request->validated('type'),
        );

        session(['active_entity_id' => $entity->id]);

        return redirect()
            ->route('dashboard')
            ->with('success', "Entity {$entity->name} berhasil dibuat.");
    }

    /**
     * Pindah entity aktif — simpan ke session.
     * Route: POST /entity/{entity}/switch
     */
    public function switch(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('switch', $entity);

        session(['active_entity_id' => $entity->id]);

        return redirect()->back()->with('success', "Berhasil pindah ke entity {$entity->name}.");
    }
}
