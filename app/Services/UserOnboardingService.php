<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserOnboardingService
{
    public function __construct(private EntityService $entityService) {}

    /**
     * Siapkan workspace pribadi. Entity bisnis dibuat nanti dari aplikasi.
     */
    public function provision(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->entityService->createEntity($user, 'Personal', 'personal');
        });
    }
}
