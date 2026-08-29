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
        if ($transaction->isLocked()) {
            return false;
        }

        if (! $transaction->isDraft()) {
            return false;
        }

        $role = $user->getEntityRole($transaction->entity);

        return in_array($role, ['owner', 'member'], true) && $this->hasEntityAccess($user, $transaction->entity);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        if ($transaction->isLocked()) {
            return false;
        }

        // Draft bisa dihapus owner/member; pending hanya owner
        if ($transaction->isPendingApproval()) {
            return $user->isOwnerOf($transaction->entity)
                && $this->hasEntityAccess($user, $transaction->entity);
        }

        return $this->update($user, $transaction);
    }

    public function submit(User $user, Transaction $transaction): bool
    {
        if (! $transaction->isDraft()) {
            return false;
        }

        $role = $user->getEntityRole($transaction->entity);

        return in_array($role, ['owner', 'member'], true)
            && $this->hasEntityAccess($user, $transaction->entity);
    }

    public function approve(User $user, Transaction $transaction): bool
    {
        if (! $transaction->isPendingApproval()) {
            return false;
        }

        return $user->isOwnerOf($transaction->entity)
            && $this->hasEntityAccess($user, $transaction->entity);
    }

    public function reject(User $user, Transaction $transaction): bool
    {
        return $this->approve($user, $transaction);
    }

    public function createAdjustment(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
    }

    public function createInterEntityTransfer(User $user): bool
    {
        return $user->entities()->wherePivot('role', 'owner')->exists();
    }

    public function viewApprovals(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
    }

    public function viewAuditLogs(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $this->hasEntityAccess($user, $entity);
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
