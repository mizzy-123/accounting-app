<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->hasEntityAccess($user, $entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isOwnerOf($account->entity)
            && $this->hasEntityAccess($user, $account->entity);
    }

    public function delete(User $user, Account $account): bool
    {
        if ($account->is_system) {
            return false;
        }

        return $this->update($user, $account);
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
