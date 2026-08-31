<?php

use App\Models\Entity;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function entitySwitchSetup(): array
{
    $owner = User::factory()->create();

    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);

    $personal->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($owner->id, ['role' => 'owner']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'personal', 'business');
}

test('switching to business entity persists when navigating sidebar routes', function () {
    ['owner' => $owner, 'personal' => $personal, 'business' => $business] = entitySwitchSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $personal->id])
        ->post(route('entity.switch', $business))
        ->assertRedirect();

    foreach (['/transactions', '/reports', '/clients', '/invoices'] as $path) {
        $this->actingAs($owner)
            ->get($path)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('activeEntity.id', $business->id)
                ->where('activeEntity.name', 'Manifestasi')
                ->where('activeEntity.type', 'business'));
    }
});

test('shared activeEntity matches session after entity switch without preset session', function () {
    ['owner' => $owner, 'business' => $business] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entity.switch', $business))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeEntity.id', $business->id)
            ->where('entityType', 'business'));
});

test('authenticated user can create a new business entity', function () {
    ['owner' => $owner, 'personal' => $personal] = entitySwitchSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $personal->id])
        ->post(route('entities.store'), ['name' => 'PT Baru', 'type' => 'business'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    $newEntity = Entity::query()->where('name', 'PT Baru')->first();

    expect($newEntity)->not->toBeNull()
        ->and($newEntity->type)->toBe('business')
        ->and($owner->isOwnerOf($newEntity))->toBeTrue();

    $this->assertEquals($newEntity->id, session('active_entity_id'));
});

test('new entity gets default chart of accounts and categories', function () {
    ['owner' => $owner] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entities.store'), ['name' => 'Startup XYZ', 'type' => 'business']);

    $entity = Entity::query()->where('name', 'Startup XYZ')->firstOrFail();

    expect($entity->accounts()->count())->toBe(11)
        ->and($entity->categories()->count())->toBe(8)
        ->and($entity->accounts()->where('name', 'Kas')->exists())->toBeTrue();
});

test('new entity appears in shared entities list after creation', function () {
    ['owner' => $owner] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entities.store'), ['name' => 'Toko Online', 'type' => 'business']);

    $newEntity = Entity::query()->where('name', 'Toko Online')->firstOrFail();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeEntity.id', $newEntity->id)
            ->where('canCreateEntity', true)
            ->where('entities', fn ($entities) => collect($entities)->contains(
                fn ($entity) => $entity['id'] === $newEntity->id && $entity['name'] === 'Toko Online',
            )));
});

test('entity name is required when creating', function () {
    ['owner' => $owner] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entities.store'), ['name' => '', 'type' => 'business'])
        ->assertSessionHasErrors('name');
});

test('entity type is required when creating', function () {
    ['owner' => $owner] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entities.store'), ['name' => 'Test Entity'])
        ->assertSessionHasErrors('type');
});

test('user without personal entity can create personal entity', function () {
    $user = User::factory()->create();
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);
    $business->users()->attach($user->id, ['role' => 'owner']);
    (new ChartOfAccountsSeeder)->run();

    $this->actingAs($user)
        ->post(route('entities.store'), ['name' => 'Keuangan Saya', 'type' => 'personal'])
        ->assertRedirect(route('dashboard'));

    $personal = Entity::query()->where('name', 'Keuangan Saya')->first();

    expect($personal)->not->toBeNull()
        ->and($personal->type)->toBe('personal')
        ->and($user->ownsPersonalEntity())->toBeTrue();
});

test('user cannot create second personal entity', function () {
    ['owner' => $owner] = entitySwitchSetup();

    $this->actingAs($owner)
        ->post(route('entities.store'), ['name' => 'Personal Kedua', 'type' => 'personal'])
        ->assertSessionHasErrors('type');
});

test('guest cannot create entity', function () {
    $this->post(route('entities.store'), ['name' => 'PT Baru', 'type' => 'business'])
        ->assertRedirect(route('login'));
});
