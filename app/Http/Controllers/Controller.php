<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function activeEntity(Request $request): Entity
    {
        /** @var Entity|null $entity */
        $entity = $request->attributes->get('active_entity');

        if (! $entity) {
            abort(403, 'Entity aktif tidak ditemukan.');
        }

        return $entity;
    }
}
