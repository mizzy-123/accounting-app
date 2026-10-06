<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\FixedAsset;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function depreciationSetup(): array
{
    $owner = User::factory()->create();
    $entity = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);
    $entity->users()->attach($owner->id, ['role' => 'owner']);
    (new ChartOfAccountsSeeder)->run();

    $accounts = Account::query()->where('entity_id', $entity->id)->get()->keyBy('name');

    return compact('owner', 'entity', 'accounts');
}

test('owner can create a fixed asset and post acquisition journal', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'Laptop Kerja',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'payment_account_id' => $accounts['Kas']->id,
            'acquisition_date' => '2026-01-15',
            'cost' => 12000000,
            'residual_value' => 0,
            'useful_life_months' => 24,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $asset = FixedAsset::query()->where('name', 'Laptop Kerja')->first();

    expect($asset)->not->toBeNull()
        ->and($asset->monthlyAmount())->toBe('500000.00');

    $acquisition = Transaction::query()
        ->where('entity_id', $entity->id)
        ->where('description', 'Perolehan aset Laptop Kerja')
        ->first();

    expect($acquisition)->not->toBeNull()
        ->and($acquisition->status)->toBe('approved')
        ->and($acquisition->amount)->toBe('12000000.00')
        ->and($acquisition->reference)->toBe("asset-acquisition:{$asset->id}");
});

test('owner can post straight line depreciation for missing months', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'Printer',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'acquisition_date' => '2026-01-01',
            'cost' => 1200000,
            'residual_value' => 0,
            'useful_life_months' => 12,
        ]);

    $asset = FixedAsset::query()->where('name', 'Printer')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.depreciate', $asset), [
            'through' => '2026-03',
        ])
        ->assertRedirect();

    $asset->refresh();

    expect($asset->depreciationEntries()->count())->toBe(3)
        ->and($asset->accumulatedAmount())->toBe('300000.00')
        ->and($asset->bookValue())->toBe('900000.00');

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.depreciate', $asset), [
            'through' => '2026-03',
        ])
        ->assertRedirect();

    expect($asset->fresh()->depreciationEntries()->count())->toBe(3);
});

test('depreciation stops at residual value', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'HP',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'acquisition_date' => '2026-01-01',
            'cost' => 300000,
            'residual_value' => 0,
            'useful_life_months' => 3,
        ]);

    $asset = FixedAsset::query()->where('name', 'HP')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.depreciate', $asset), [
            'through' => '2026-12',
        ]);

    $asset->refresh();

    expect($asset->status)->toBe('fully_depreciated')
        ->and($asset->depreciationEntries()->count())->toBe(3)
        ->and($asset->bookValue())->toBe('0.00');
});

test('owner can dispose an asset and record a loss', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'Kamera',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'acquisition_date' => '2026-01-01',
            'cost' => 300000,
            'residual_value' => 0,
            'useful_life_months' => 3,
        ]);

    $asset = FixedAsset::query()->where('name', 'Kamera')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.depreciate', $asset), [
            'through' => '2026-01',
        ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.dispose', $asset), [
            'date' => '2026-01-20',
            'proceeds' => 150000,
            'proceeds_account_id' => $accounts['Kas']->id,
            'gain_account_id' => $accounts['Pendapatan Pelepasan Aset']->id,
            'loss_account_id' => $accounts['Kerugian Pelepasan Aset']->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $asset->refresh();

    expect($asset->status)->toBe('disposed');

    $disposal = Transaction::query()
        ->where('reference', "asset-disposal:{$asset->id}")
        ->first();

    expect($disposal)->not->toBeNull()
        ->and($disposal->status)->toBe('approved');

    $entries = $disposal->entries()->get()->keyBy('account_id');

    expect($entries[$accounts['Akumulasi Penyusutan']->id]->debit)->toBe('100000.00')
        ->and($entries[$accounts['Kas']->id]->debit)->toBe('150000.00')
        ->and($entries[$accounts['Kerugian Pelepasan Aset']->id]->debit)->toBe('50000.00')
        ->and($entries[$accounts['Aset Tetap']->id]->kredit)->toBe('300000.00');
});

test('owner can dispose a fully depreciated asset', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'Tablet',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'acquisition_date' => '2026-01-01',
            'cost' => 300000,
            'residual_value' => 0,
            'useful_life_months' => 3,
        ]);

    $asset = FixedAsset::query()->where('name', 'Tablet')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.depreciate', $asset), [
            'through' => '2026-12',
        ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.dispose', $asset), [
            'date' => '2026-04-01',
            'proceeds' => 0,
            'gain_account_id' => $accounts['Pendapatan Pelepasan Aset']->id,
            'loss_account_id' => $accounts['Kerugian Pelepasan Aset']->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($asset->fresh()->status)->toBe('disposed');
});

test('scheduled depreciation posts missing months for active assets', function () {
    ['owner' => $owner, 'entity' => $entity, 'accounts' => $accounts] = depreciationSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post(route('assets.store'), [
            'name' => 'Monitor',
            'asset_account_id' => $accounts['Aset Tetap']->id,
            'accumulated_account_id' => $accounts['Akumulasi Penyusutan']->id,
            'expense_account_id' => $accounts['Beban Penyusutan']->id,
            'acquisition_date' => '2026-01-01',
            'cost' => 1200000,
            'residual_value' => 0,
            'useful_life_months' => 12,
        ]);

    $this->artisan('accounting:process-depreciation', ['--through' => '2026-03'])
        ->assertSuccessful();

    $asset = FixedAsset::query()->where('name', 'Monitor')->firstOrFail();

    expect($asset->depreciationEntries()->count())->toBe(3)
        ->and($asset->accumulatedAmount())->toBe('300000.00');
});
