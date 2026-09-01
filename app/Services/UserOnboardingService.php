<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserOnboardingService
{
    public function __construct(private EntityService $entityService) {}

    /**
     * Siapkan workspace default untuk user SaaS baru.
     */
    public function provision(User $user, ?string $businessName = null): void
    {
        DB::transaction(function () use ($user, $businessName): void {
            $this->entityService->createEntity($user, 'Personal', 'personal');
            $this->entityService->createEntity(
                $user,
                $businessName ?: 'Bisnis Saya',
                'business',
            );
        });
    }
}
