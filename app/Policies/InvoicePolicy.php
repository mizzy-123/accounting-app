<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        $entity = $invoice->entity;

        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        $role = $user->getEntityRole($entity);

        return $entity->isBusiness() && in_array($role, ['owner', 'member'], true);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        $entity = $invoice->entity;
        $role = $user->getEntityRole($entity);

        return $entity->isBusiness() && in_array($role, ['owner', 'member'], true);
    }

    /**
     * Hanya owner yang bisa ubah status (sent/paid).
     */
    public function updateStatus(User $user, Invoice $invoice): bool
    {
        return $invoice->entity->isBusiness() && $user->isOwnerOf($invoice->entity);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $invoice->entity->isBusiness()
            && $user->isOwnerOf($invoice->entity)
            && $invoice->isDraft();
    }
}
