<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Entity;
use App\Models\User;

class ClientPolicy
{
    /**
     * Semua user yang punya akses ke entity bisnis bisa melihat clients.
     */
    public function viewAny(User $user, Entity $entity): bool
    {
        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    public function view(User $user, Client $client): bool
    {
        $entity = $client->entity;

        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    /**
     * Hanya owner entity bisnis yang bisa create/update/delete.
     */
    public function create(User $user, Entity $entity): bool
    {
        return $entity->isBusiness() && $user->isOwnerOf($entity);
    }

    public function update(User $user, Client $client): bool
    {
        return $client->entity->isBusiness() && $user->isOwnerOf($client->entity);
    }

    public function delete(User $user, Client $client): bool
    {
        return $client->entity->isBusiness() && $user->isOwnerOf($client->entity);
    }
}
