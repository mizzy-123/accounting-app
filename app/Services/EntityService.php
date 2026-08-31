<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EntityService
{
    public function __construct(private EntitySetupService $entitySetup) {}

    /**
     * Buat entity baru dan assign creator sebagai owner.
     */
    public function createEntity(User $user, string $name, string $type): Entity
    {
        if ($type === 'personal' && $user->ownsPersonalEntity()) {
            throw new InvalidArgumentException('User sudah memiliki entity pribadi.');
        }

        return DB::transaction(function () use ($user, $name, $type): Entity {
            $entity = Entity::create([
                'name' => $name,
                'type' => $type,
            ]);

            $entity->users()->attach($user->id, ['role' => 'owner']);

            $this->entitySetup->seedDefaults($entity);

            return $entity;
        });
    }
}
