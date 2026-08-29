<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
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
