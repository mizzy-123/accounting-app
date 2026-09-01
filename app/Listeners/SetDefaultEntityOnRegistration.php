<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

class SetDefaultEntityOnRegistration
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $personal = $user->entities()
            ->where('entities.type', 'personal')
            ->first();

        if ($personal) {
            session(['active_entity_id' => $personal->id]);
        }
    }
}
