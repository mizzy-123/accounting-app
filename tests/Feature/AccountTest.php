<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function accountSetup(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $entity = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);
    $entity->users()->attach($owner->id, ['role' => 'owner']);
    $entity->users()->attach($member->id, ['role' => 'member']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'member', 'entity');
}

test('owner can view chart of accounts', function () {
    ['owner' => $owner, 'entity' => $entity] = accountSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get(route('accounts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('accounts/index')
            ->has('accounts')
            ->where('canManage', true));
});

test('owner can create a custom account', function () {
    ['owner' => $owner, 'entity' => $entity] = accountSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('accounts.store'), [
            'name' => 'Inventaris',
            'type' => 'asset',
        ])
        ->assertRedirect();

    $account = Account::query()->where('name', 'Inventaris')->first();

    expect($account)->not->toBeNull()
        ->and($account->entity_id)->toBe($entity->id)
        ->and($account->is_system)->toBeFalse()
        ->and($account->type)->toBe('asset');
});

test('member cannot create custom account', function () {
    ['member' => $member, 'entity' => $entity] = accountSetup();

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('accounts.store'), [
            'name' => 'Inventaris',
            'type' => 'asset',
        ])
        ->assertForbidden();
});

test('system account cannot be deleted', function () {
    ['owner' => $owner, 'entity' => $entity] = accountSetup();

    $kas = Account::query()->where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->delete(route('accounts.destroy', $kas))
        ->assertForbidden();

    expect(Account::query()->whereKey($kas->id)->exists())->toBeTrue();
});

test('custom account without journals can be deleted', function () {
    ['owner' => $owner, 'entity' => $entity] = accountSetup();

    $account = Account::create([
        'entity_id' => $entity->id,
        'name' => 'Pajak',
        'type' => 'liability',
        'is_active' => true,
        'is_system' => false,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect();

    expect(Account::query()->whereKey($account->id)->exists())->toBeFalse();
});
