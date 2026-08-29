<?php

use App\Exceptions\UnbalancedTransactionException;
use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOwnerWithEntities(): array
{
    $owner = User::factory()->create();

    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);

    $personal->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($owner->id, ['role' => 'owner']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'personal', 'business');
}

function assetAccount(Entity $entity): Account
{
    return Account::where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
}

function bankAccount(Entity $entity): Account
{
    return Account::where('entity_id', $entity->id)->where('name', 'Bank')->firstOrFail();
}

function incomeCategory(Entity $entity): Category
{
    return Category::where('entity_id', $entity->id)->where('type', 'income')->firstOrFail();
}

function expenseCategory(Entity $entity): Category
{
    return Category::where('entity_id', $entity->id)->where('type', 'expense')->firstOrFail();
}

test('income transaction creates balanced double-entry journal', function () {
    ['owner' => $owner, 'personal' => $entity] = createOwnerWithEntities();
    $service = app(TransactionService::class);

    $transaction = $service->createIncome($entity, $owner, [
        'date' => '2026-08-29',
        'description' => 'Gaji bulan ini',
        'amount' => 5000000,
        'account_id' => assetAccount($entity)->id,
        'category_id' => incomeCategory($entity)->id,
    ]);

    expect($transaction->type)->toBe('income')
        ->and($transaction->entries)->toHaveCount(2);

    $totalDebit = $transaction->entries->sum('debit');
    $totalKredit = $transaction->entries->sum('kredit');

    expect((float) $totalDebit)->toBe(5000000.0)
        ->and((float) $totalKredit)->toBe(5000000.0);
});

test('expense transaction creates balanced double-entry journal', function () {
    ['owner' => $owner, 'personal' => $entity] = createOwnerWithEntities();
    $service = app(TransactionService::class);

    $transaction = $service->createExpense($entity, $owner, [
        'date' => '2026-08-29',
        'amount' => 150000,
        'account_id' => assetAccount($entity)->id,
        'category_id' => expenseCategory($entity)->id,
    ]);

    $totalDebit = $transaction->entries->sum('debit');
    $totalKredit = $transaction->entries->sum('kredit');

    expect((float) $totalDebit)->toBe(150000.0)
        ->and((float) $totalKredit)->toBe(150000.0);
});

test('transfer moves amount between asset accounts', function () {
    ['owner' => $owner, 'personal' => $entity] = createOwnerWithEntities();
    $service = app(TransactionService::class);

    $transaction = $service->createTransfer($entity, $owner, [
        'date' => '2026-08-29',
        'amount' => 1000000,
        'from_account_id' => assetAccount($entity)->id,
        'to_account_id' => bankAccount($entity)->id,
    ]);

    expect($transaction->type)->toBe('transfer')
        ->and($transaction->entries)->toHaveCount(2);
});

test('inter entity transfer creates linked transactions in both entities', function () {
    ['owner' => $owner, 'personal' => $personal, 'business' => $business] = createOwnerWithEntities();
    $service = app(TransactionService::class);

    [$outgoing, $incoming] = $service->createInterEntityTransfer(
        $business,
        $personal,
        $owner,
        [
            'date' => '2026-08-29',
            'amount' => 2000000,
            'from_account_id' => assetAccount($business)->id,
            'to_account_id' => bankAccount($personal)->id,
            'description' => 'Owner draw',
        ],
    );

    expect($outgoing->reference)->not->toBeNull()
        ->and($incoming->reference)->toBe($outgoing->reference)
        ->and($outgoing->entity_id)->toBe($business->id)
        ->and($incoming->entity_id)->toBe($personal->id);
});

test('manual journal rejects unbalanced entries', function () {
    ['owner' => $owner, 'personal' => $entity] = createOwnerWithEntities();
    $service = app(TransactionService::class);
    $kas = assetAccount($entity);
    $bank = bankAccount($entity);

    $service->createAdjustment($entity, $owner, [
        'date' => '2026-08-29',
        'entries' => [
            ['account_id' => $kas->id, 'debit' => 1000, 'kredit' => 0],
            ['account_id' => $bank->id, 'debit' => 0, 'kredit' => 500],
        ],
    ]);
})->throws(UnbalancedTransactionException::class);

test('owner can create transaction via http', function () {
    ['owner' => $owner, 'personal' => $entity] = createOwnerWithEntities();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/transactions', [
            'type' => 'expense',
            'date' => '2026-08-29',
            'amount' => 50000,
            'account_id' => assetAccount($entity)->id,
            'category_id' => expenseCategory($entity)->id,
            'description' => 'Makan siang',
        ])
        ->assertRedirect();

    expect(Transaction::count())->toBe(1);
});

test('viewer cannot create transaction via http', function () {
    ['owner' => $owner, 'business' => $entity] = createOwnerWithEntities();
    $viewer = User::factory()->create();
    $entity->users()->attach($viewer->id, ['role' => 'viewer']);

    $this->actingAs($viewer)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/transactions', [
            'type' => 'expense',
            'date' => '2026-08-29',
            'amount' => 50000,
            'account_id' => assetAccount($entity)->id,
            'category_id' => expenseCategory($entity)->id,
        ])
        ->assertForbidden();
});

test('member cannot access manual journal', function () {
    ['business' => $entity] = createOwnerWithEntities();
    $member = User::factory()->create();
    $entity->users()->attach($member->id, ['role' => 'member']);

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/journals/create')
        ->assertForbidden();
});

test('owner can access manual journal page', function () {
    ['owner' => $owner, 'business' => $entity] = createOwnerWithEntities();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/journals/create')
        ->assertOk();
});
