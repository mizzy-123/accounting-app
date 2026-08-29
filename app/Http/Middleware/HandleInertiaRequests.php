<?php

namespace App\Http\Middleware;

use App\Models\Entity;
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
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Entity aktif dari request attribute (set oleh EnsureEntityAccess middleware)
        $activeEntity = $request->attributes->get('active_entity');

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
        ];
    }
}

