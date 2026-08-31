<?php

namespace App\Http\Middleware;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        // Entity aktif — resolve dari request attribute (post middleware) atau session
        $activeEntity = $this->resolveActiveEntity($request, $user);

        // Daftar entity yang bisa diakses user (personal hanya untuk owner)
        $entities = null;
        if ($user) {
            $entities = $user->entities()
                ->get()
                ->filter(function (Entity $entity) use ($user) {
                    if ($entity->isPersonal()) {
                        return $user->isOwnerOf($entity);
                    }

                    return true;
                })
                ->map(fn (Entity $entity) => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                    'type' => $entity->type,
                    'role' => $entity->pivot->role,
                ])
                ->values();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'activeEntity' => $activeEntity ? [
                'id' => $activeEntity->id,
                'name' => $activeEntity->name,
                'type' => $activeEntity->type,
                'role' => $user?->getEntityRole($activeEntity),
            ] : null,
            'entities' => $entities,
            'canCreateEntity' => (bool) $user,
            'canCreatePersonalEntity' => $user ? ! $user->ownsPersonalEntity() : false,
        ];
    }

    /**
     * Resolve entity aktif untuk shared Inertia props dari session
     * (route middleware `entity.access` set attribute untuk controller).
     */
    private function resolveActiveEntity(Request $request, ?User $user): ?Entity
    {
        if (! $user) {
            return null;
        }

        $fromRequest = $request->attributes->get('active_entity');
        if ($fromRequest instanceof Entity) {
            return $fromRequest;
        }

        $activeEntityId = session('active_entity_id');
        if ($activeEntityId) {
            $entity = Entity::find($activeEntityId);
            if ($entity && $user->hasAccessTo($entity)) {
                if ($entity->isPersonal() && ! $user->isOwnerOf($entity)) {
                    // member/viewer tidak boleh personal — lanjut ke default
                } else {
                    return $entity;
                }
            }
        }

        $entities = $user->entities()->get();

        return $entities->firstWhere(fn (Entity $entity) => $entity->isPersonal() && $user->isOwnerOf($entity))
            ?? $entities->firstWhere(fn (Entity $entity) => $entity->isBusiness())
            ?? $entities->first();
    }
}
