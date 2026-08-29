<?php

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function approvalSetup(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();
    $outsider = User::factory()->create();

    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);

    $personal->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($member->id, ['role' => 'member']);
    $business->users()->attach($viewer->id, ['role' => 'viewer']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'member', 'viewer', 'outsider', 'personal', 'business');
}

function makeDraftExpense(Entity $entity, User $user): Transaction
{
    $kas = Account::query()->where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
    $category = Category::query()->where('entity_id', $entity->id)->where('type', 'expense')->firstOrFail();

    return app(TransactionService::class)->createExpense($entity, $user, [
        'date' => '2026-08-29',
        'amount' => 100_000,
        'account_id' => $kas->id,
        'category_id' => $category->id,
        'description' => 'Test expense',
    ]);
}

test('creating a transaction writes audit logs for header and entries', function () {
    ['owner' => $owner, 'business' => $entity] = approvalSetup();

    $this->actingAs($owner);
    $transaction = makeDraftExpense($entity, $owner);

    expect(AuditLog::query()->where('model_type', Transaction::class)->where('model_id', $transaction->id)->exists())
        ->toBeTrue()
        ->and(AuditLog::query()->where('action', 'created')->count())->toBeGreaterThanOrEqual(3);
});

test('owner can submit approve and lock a transaction', function () {
    ['owner' => $owner, 'business' => $entity] = approvalSetup();
    $transaction = makeDraftExpense($entity, $owner);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post("/transactions/{$transaction->id}/submit")
        ->assertRedirect();

    expect($transaction->fresh()->status)->toBe('pending_approval');

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post("/transactions/{$transaction->id}/approve")
        ->assertRedirect();

    $fresh = $transaction->fresh();
    expect($fresh->status)->toBe('approved')
        ->and($fresh->approved_by)->toBe($owner->id)
        ->and($fresh->approved_at)->not->toBeNull();

    expect(AuditLog::query()->where('action', 'approved')->where('model_id', $transaction->id)->exists())
        ->toBeTrue();
});

test('member can submit but cannot approve', function () {
    ['owner' => $owner, 'member' => $member, 'business' => $entity] = approvalSetup();
    $transaction = makeDraftExpense($entity, $member);

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->post("/transactions/{$transaction->id}/submit")
        ->assertRedirect();

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->post("/transactions/{$transaction->id}/approve")
        ->assertForbidden();
});

test('approved transaction cannot be deleted', function () {
    ['owner' => $owner, 'business' => $entity] = approvalSetup();
    $service = app(TransactionService::class);
    $transaction = makeDraftExpense($entity, $owner);
    $service->submitForApproval($transaction);
    $service->approve($transaction->fresh(), $owner);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->delete("/transactions/{$transaction->id}")
        ->assertForbidden();

    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeTrue();
});

test('draft transaction can be deleted and is audited', function () {
    ['owner' => $owner, 'business' => $entity] = approvalSetup();
    $transaction = makeDraftExpense($entity, $owner);
    $id = $transaction->id;

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->delete("/transactions/{$id}")
        ->assertRedirect('/transactions');

    expect(Transaction::query()->whereKey($id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'deleted')->where('model_id', $id)->exists())->toBeTrue();
});

test('owner can view pending approvals and audit logs pages', function () {
    ['owner' => $owner, 'business' => $entity] = approvalSetup();
    $transaction = makeDraftExpense($entity, $owner);
    app(TransactionService::class)->submitForApproval($transaction);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/approvals')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('approvals/index')
            ->has('transactions', 1));

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/audit-logs')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('audit-logs/index')->has('logs.data'));
});

test('member and viewer cannot access approvals or audit logs', function () {
    ['member' => $member, 'viewer' => $viewer, 'business' => $entity] = approvalSetup();

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/approvals')
        ->assertForbidden();

    $this->actingAs($viewer)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/audit-logs')
        ->assertForbidden();
});

test('member cannot access personal entity data via switch or transactions', function () {
    ['owner' => $owner, 'member' => $member, 'personal' => $personal, 'business' => $business] = approvalSetup();
    $personalTx = makeDraftExpense($personal, $owner);

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $business->id])
        ->post("/entity/{$personal->id}/switch")
        ->assertForbidden();

    // Session personal tanpa akses → middleware reset ke entity bisnis, data personal tidak bocor
    $this->actingAs($member)
        ->withSession(['active_entity_id' => $personal->id])
        ->get('/transactions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('transactions.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['id'] !== $personalTx->id,
            )));

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $business->id])
        ->get("/transactions/{$personalTx->id}")
        ->assertForbidden();
});

test('viewer cannot create transactions on business entity', function () {
    ['viewer' => $viewer, 'business' => $entity] = approvalSetup();
    $kas = Account::query()->where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
    $category = Category::query()->where('entity_id', $entity->id)->where('type', 'expense')->firstOrFail();

    $this->actingAs($viewer)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/transactions', [
            'type' => 'expense',
            'date' => '2026-08-29',
            'amount' => 50000,
            'account_id' => $kas->id,
            'category_id' => $category->id,
        ])
        ->assertForbidden();
});

test('outsider without entity access is blocked from business endpoints', function () {
    ['outsider' => $outsider, 'business' => $entity] = approvalSetup();

    $this->actingAs($outsider)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/transactions')
        ->assertForbidden();
});
