<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\RecurringTransaction;
use App\Models\User;

class RecurringTransactionPolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->canManage($user, $entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        return $this->canManage($user, $entity);
    }

    public function delete(User $user, RecurringTransaction $recurringTransaction): bool
    {
        return $this->canManage($user, $recurringTransaction->entity);
    }

    private function canManage(User $user, Entity $entity): bool
    {
        $role = $user->getEntityRole($entity);

        if (! in_array($role, ['owner', 'member'], true)) {
            return false;
        }

        if ($entity->isPersonal() && ! $user->isOwnerOf($entity)) {
            return false;
        }

        return true;
    }
}
