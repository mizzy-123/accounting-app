<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Semua user entity bisnis bisa melihat list projects.
     */
    public function viewAny(User $user, Entity $entity): bool
    {
        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    public function view(User $user, Project $project): bool
    {
        $entity = $project->entity;

        return $entity->isBusiness() && $user->hasAccessTo($entity);
    }

    /**
     * Owner dan member bisa buat project.
     */
    public function create(User $user, Entity $entity): bool
    {
        $role = $user->getEntityRole($entity);

        return $entity->isBusiness() && in_array($role, ['owner', 'member']);
    }

    /**
     * Owner dan member bisa edit project.
     */
    public function update(User $user, Project $project): bool
    {
        $entity = $project->entity;
        $role = $user->getEntityRole($entity);

        return $entity->isBusiness() && in_array($role, ['owner', 'member']);
    }

    /**
     * Hanya owner yang bisa hapus project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->entity->isBusiness() && $user->isOwnerOf($project->entity);
    }
}
