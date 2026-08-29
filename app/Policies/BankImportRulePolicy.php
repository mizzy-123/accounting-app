<?php

namespace App\Policies;

use App\Models\BankImportRule;
use App\Models\Entity;
use App\Models\User;

class BankImportRulePolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->canManage($user, $entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        return $this->canManage($user, $entity);
    }

    public function update(User $user, BankImportRule $rule): bool
    {
        return $this->canManage($user, $rule->entity);
    }

    public function delete(User $user, BankImportRule $rule): bool
    {
        return $this->canManage($user, $rule->entity);
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
