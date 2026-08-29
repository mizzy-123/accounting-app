<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\Entity;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->canAccess($user, $entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        return $this->canManage($user, $entity);
    }

    public function update(User $user, Budget $budget): bool
    {
        return $this->canManage($user, $budget->entity);
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $this->canManage($user, $budget->entity);
    }

    private function canAccess(User $user, Entity $entity): bool
    {
        if ($entity->isPersonal() && ! $user->isOwnerOf($entity)) {
            return false;
        }

        return $user->hasAccessTo($entity);
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
