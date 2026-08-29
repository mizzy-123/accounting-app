<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardSetup(): array
{
    $owner = User::factory()->create();

    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);

    $personal->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($owner->id, ['role' => 'owner']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'personal', 'business');
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    ['owner' => $owner, 'personal' => $personal] = dashboardSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $personal->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('accounts')
            ->has('totals')
            ->has('chart')
            ->where('entityType', 'personal'));
});

test('dashboard shows real account balances from transactions', function () {
    ['owner' => $owner, 'personal' => $entity] = dashboardSetup();
    $service = app(TransactionService::class);

    $kas = Account::where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
    $category = Category::where('entity_id', $entity->id)->where('type', 'income')->firstOrFail();

    $service->createIncome($entity, $owner, [
        'date' => now()->toDateString(),
        'amount' => 1000000,
        'account_id' => $kas->id,
        'category_id' => $category->id,
    ]);

    $dashboard = app(DashboardService::class)->forEntity($entity);
    $kasBalance = collect($dashboard['accounts'])->firstWhere('name', 'Kas');

    expect($kasBalance)->not->toBeNull()
        ->and($kasBalance['balance'])->toBe('1000000.00')
        ->and($dashboard['totals']['income_this_month'])->toBe('1000000.00');
});

test('dashboard cash flow chart includes income and expense data', function () {
    ['owner' => $owner, 'personal' => $entity] = dashboardSetup();
    $transactionService = app(TransactionService::class);
    $dashboardService = app(DashboardService::class);

    $kas = Account::where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
    $incomeCategory = Category::where('entity_id', $entity->id)->where('type', 'income')->firstOrFail();
    $expenseCategory = Category::where('entity_id', $entity->id)->where('type', 'expense')->firstOrFail();

    $transactionService->createIncome($entity, $owner, [
        'date' => now()->toDateString(),
        'amount' => 500000,
        'account_id' => $kas->id,
        'category_id' => $incomeCategory->id,
    ]);

    $transactionService->createExpense($entity, $owner, [
        'date' => now()->toDateString(),
        'amount' => 200000,
        'account_id' => $kas->id,
        'category_id' => $expenseCategory->id,
    ]);

    $monthly = $dashboardService->cashFlowSeries($entity, 'monthly');
    $currentMonth = collect($monthly)->last();

    expect($currentMonth['income'])->toBe('500000.00')
        ->and($currentMonth['expense'])->toBe('200000.00');
});

test('dashboard data changes when active entity is switched', function () {
    ['owner' => $owner, 'personal' => $personal, 'business' => $business] = dashboardSetup();
    $transactionService = app(TransactionService::class);

    $personalKas = Account::where('entity_id', $personal->id)->where('name', 'Kas')->firstOrFail();
    $businessKas = Account::where('entity_id', $business->id)->where('name', 'Kas')->firstOrFail();
    $incomeCategoryPersonal = Category::where('entity_id', $personal->id)->where('type', 'income')->firstOrFail();
    $incomeCategoryBusiness = Category::where('entity_id', $business->id)->where('type', 'income')->firstOrFail();

    $transactionService->createIncome($personal, $owner, [
        'date' => now()->toDateString(),
        'amount' => 100000,
        'account_id' => $personalKas->id,
        'category_id' => $incomeCategoryPersonal->id,
    ]);

    $transactionService->createIncome($business, $owner, [
        'date' => now()->toDateString(),
        'amount' => 5000000,
        'account_id' => $businessKas->id,
        'category_id' => $incomeCategoryBusiness->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $personal->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('totals.income_this_month', '100000.00'));

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $business->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('totals.income_this_month', '5000000.00')
            ->where('entityType', 'business'));
});
