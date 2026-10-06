<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\FixedAsset;
use App\Models\User;

class FixedAssetPolicy
{
    public function viewAny(User $user, Entity $entity): bool
    {
        return $this->hasEntityAccess($user, $entity);
    }

    public function create(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
    }

    public function update(User $user, FixedAsset $fixedAsset): bool
    {
        return $user->isOwnerOf($fixedAsset->entity)
            && $this->hasEntityAccess($user, $fixedAsset->entity);
    }

    public function delete(User $user, FixedAsset $fixedAsset): bool
    {
        return $this->update($user, $fixedAsset);
    }

    public function depreciate(User $user, FixedAsset $fixedAsset): bool
    {
        return $this->update($user, $fixedAsset);
    }

    public function dispose(User $user, FixedAsset $fixedAsset): bool
    {
        return $this->update($user, $fixedAsset);
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
