<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->hasEntityAccess($user, $entity);
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->hasEntityAccess($user, $transaction->entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        $role = $user->getEntityRole($entity);

        return in_array($role, ['owner', 'member'], true) && $this->hasEntityAccess($user, $entity);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        if (! $transaction->isDraft()) {
            return false;
        }

        $role = $user->getEntityRole($transaction->entity);

        return in_array($role, ['owner', 'member'], true) && $this->hasEntityAccess($user, $transaction->entity);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }

    public function createAdjustment(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
    }

    public function createInterEntityTransfer(User $user): bool
    {
        return $user->entities()->wherePivot('role', 'owner')->exists();
    }

    private function hasEntityAccess(User $user, Entity $entity): bool
    {
        if (! $user->hasAccessTo($entity)) {
            return false;
        }

        if ($entity->isPersonal() && ! $user->isOwnerOf($entity)) {
            return false;
        }

        return true;
    }
}
