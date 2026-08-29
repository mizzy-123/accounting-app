<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Semua entity yang bisa diakses user ini (via pivot entity_user).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Entity, $this>
     */
    public function entities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'entity_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Dapatkan role user di entity tertentu. Null jika tidak punya akses.
     */
    public function getEntityRole(Entity $entity): ?string
    {
        $pivot = $this->entities()
            ->where('entities.id', $entity->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Cek apakah user adalah owner di entity tertentu.
     */
    public function isOwnerOf(Entity $entity): bool
    {
        return $this->getEntityRole($entity) === 'owner';
    }

    /**
     * Cek apakah user punya akses ke entity tertentu.
     */
    public function hasAccessTo(Entity $entity): bool
    {
        return $this->getEntityRole($entity) !== null;
    }
}
