<?php

namespace App\Http\Middleware;

use App\Models\Entity;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ini:
 * 1. Membaca active_entity_id dari session
 * 2. Verifikasi user punya akses ke entity tersebut
 * 3. Blokir akses ke entity personal untuk non-owner
 * 4. Set entity aktif di request untuk dipakai controller
 */
class EnsureEntityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Ambil entity aktif dari session
        $activeEntityId = session('active_entity_id');

        if ($activeEntityId) {
            $entity = Entity::find($activeEntityId);

            if ($entity && $user->hasAccessTo($entity)) {
                // Cek personal entity: hanya owner
                if ($entity->isPersonal() && ! $user->isOwnerOf($entity)) {
                    // Reset ke entity bisnis yang bisa diakses
                    $this->resetToAccessibleEntity($request, $user);

                    return redirect()->route('dashboard')
                        ->with('error', 'Anda tidak memiliki akses ke entity Personal.');
                }

                // Simpan entity aktif ke request untuk dipakai controller/Inertia
                $request->attributes->set('active_entity', $entity);

                return $next($request);
            }
        }

        // Belum ada entity aktif atau entity tidak valid — set default
        $this->resetToAccessibleEntity($request, $user);

        // Kalau masih tidak ada entity sama sekali
        if (! $request->attributes->has('active_entity')) {
            return $next($request);
        }

        return $next($request);
    }

    /**
     * Set entity aktif ke entity pertama yang bisa diakses user.
     * Prioritaskan entity bisnis (untuk member/viewer yang tidak bisa akses personal).
     */
    private function resetToAccessibleEntity(Request $request, User $user): void
    {
        // Owner: default ke personal
        // Member/viewer: default ke entity bisnis pertama
        $entities = $user->entities()->get();

        $defaultEntity = $entities->firstWhere(fn ($e) => $e->isPersonal() && $user->isOwnerOf($e))
            ?? $entities->firstWhere(fn ($e) => $e->isBusiness())
            ?? $entities->first();

        if ($defaultEntity) {
            session(['active_entity_id' => $defaultEntity->id]);
            $request->attributes->set('active_entity', $defaultEntity);
        }
    }
}
