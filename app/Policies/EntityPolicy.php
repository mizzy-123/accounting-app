<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    /**
     * User yang sudah login bisa membuat entity bisnis baru.
     * Entity personal tidak bisa dibuat lewat UI (hanya via seeder).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Cek apakah user bisa melihat/mengakses entity ini.
     * Personal entity hanya bisa diakses owner.
     */
    public function view(User $user, Entity $entity): bool
    {
        $role = $user->getEntityRole($entity);

        if ($role === null) {
            return false;
        }

        // Entity personal hanya untuk owner
        if ($entity->isPersonal() && $role !== 'owner') {
            return false;
        }

        return true;
    }

    /**
     * Cek apakah user bisa mengelola entity (owner only).
     */
    public function manage(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity);
    }

    /**
     * Cek apakah user bisa mengundang/mengelola anggota tim.
     * Hanya owner, dan hanya untuk entity bisnis.
     */
    public function inviteMember(User $user, Entity $entity): bool
    {
        return $user->isOwnerOf($entity) && $entity->isBusiness();
    }

    /**
     * Cek apakah user bisa switch ke entity ini.
     */
    public function switch(User $user, Entity $entity): bool
    {
        return $this->view($user, $entity);
    }
}
