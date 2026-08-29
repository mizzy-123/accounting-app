<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Tampilkan halaman tim entity.
     * Route: GET /entity/{entity}/team
     */
    public function index(Entity $entity): Response
    {
        $this->authorize('inviteMember', $entity);

        $members = $entity->users()
            ->select(['users.id', 'users.name', 'users.email'])
            ->withPivot('role')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]);

        return Inertia::render('team/index', [
            'entity' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'type' => $entity->type,
            ],
            'members' => $members,
        ]);
    }

    /**
     * Invite/tambah anggota ke entity (hanya entity bisnis).
     * Kalau user belum terdaftar di sistem, tolak — user harus register dulu.
     * Route: POST /entity/{entity}/team
     */
    public function store(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('inviteMember', $entity);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(['member', 'viewer'])],
        ], [
            'email.exists' => 'User dengan email tersebut belum terdaftar. Minta mereka register terlebih dahulu.',
        ]);

        $invitedUser = User::where('email', $validated['email'])->firstOrFail();

        // Cegah invite diri sendiri
        if ($invitedUser->id === $request->user()->id) {
            return back()->withErrors(['email' => 'Anda tidak bisa mengundang diri sendiri.']);
        }

        // Cegah duplikasi — update role jika sudah ada
        $entity->users()->syncWithoutDetaching([
            $invitedUser->id => ['role' => $validated['role']],
        ]);

        return back()->with('success', "{$invitedUser->name} berhasil ditambahkan sebagai {$validated['role']}.");
    }

    /**
     * Update role anggota tim.
     * Route: PATCH /entity/{entity}/team/{user}
     */
    public function update(Request $request, Entity $entity, User $user): RedirectResponse
    {
        $this->authorize('inviteMember', $entity);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['member', 'viewer'])],
        ]);

        // Tidak bisa ubah role owner
        $currentRole = $user->getEntityRole($entity);
        if ($currentRole === 'owner') {
            return back()->withErrors(['role' => 'Role owner tidak bisa diubah.']);
        }

        $entity->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', "Role {$user->name} berhasil diubah menjadi {$validated['role']}.");
    }

    /**
     * Hapus anggota dari entity.
     * Route: DELETE /entity/{entity}/team/{user}
     */
    public function destroy(Entity $entity, User $user): RedirectResponse
    {
        $this->authorize('inviteMember', $entity);

        // Tidak bisa hapus owner
        if ($user->isOwnerOf($entity)) {
            return back()->withErrors(['user' => 'Owner tidak bisa dihapus dari entity.']);
        }

        $entity->users()->detach($user->id);

        return back()->with('success', "{$user->name} berhasil dihapus dari tim.");
    }
}
