<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Client;
use App\Models\Entity;
use App\Models\Project;
use App\Models\User;
use App\Services\ReportService;
use App\Services\TransactionService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function reportSetup(): array
{
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);

    $personal->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($viewer->id, ['role' => 'viewer']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'viewer', 'personal', 'business');
}

function kas(Entity $entity)
{
    return Account::query()
        ->where('entity_id', $entity->id)
        ->where('name', 'Kas')
        ->firstOrFail();
}

function catExpense(Entity $entity, string $name = 'Transport'): Category
{
    return Category::query()
        ->where('entity_id', $entity->id)
        ->where('name', $name)
        ->firstOrFail();
}

function catIncome(Entity $entity): Category
{
    return Category::query()
        ->where('entity_id', $entity->id)
        ->where('type', 'income')
        ->firstOrFail();
}

test('balance sheet balances after income and expense', function () {
    ['owner' => $owner, 'business' => $entity] = reportSetup();
    $service = app(TransactionService::class);

    $service->createIncome($entity, $owner, [
        'date' => '2026-08-01',
        'amount' => 5_000_000,
        'account_id' => kas($entity)->id,
        'category_id' => catIncome($entity)->id,
        'description' => 'Fee',
    ]);

    $service->createExpense($entity, $owner, [
        'date' => '2026-08-10',
        'amount' => 1_000_000,
        'account_id' => kas($entity)->id,
        'category_id' => catExpense($entity)->id,
        'description' => 'Ops',
    ]);

    $sheet = app(ReportService::class)->balanceSheet($entity, Carbon::parse('2026-08-31'));

    expect($sheet['totals']['is_balanced'])->toBeTrue()
        ->and((float) $sheet['totals']['assets'])->toBe(4_000_000.0)
        ->and((float) $sheet['totals']['liabilities_and_equity'])->toBe(4_000_000.0);
});

test('cash flow and category breakdown reflect transactions', function () {
    ['owner' => $owner, 'personal' => $entity] = reportSetup();
    $service = app(TransactionService::class);

    $service->createIncome($entity, $owner, [
        'date' => '2026-08-05',
        'amount' => 3_000_000,
        'account_id' => kas($entity)->id,
        'category_id' => catIncome($entity)->id,
    ]);

    $service->createExpense($entity, $owner, [
        'date' => '2026-08-12',
        'amount' => 250_000,
        'account_id' => kas($entity)->id,
        'category_id' => catExpense($entity)->id,
    ]);

    $report = app(ReportService::class)->cashFlow(
        $entity,
        Carbon::parse('2026-08-01'),
        Carbon::parse('2026-08-31'),
    );

    expect((float) $report['totals']['income'])->toBe(3_000_000.0)
        ->and((float) $report['totals']['expense'])->toBe(250_000.0)
        ->and($report['categories'])->not->toBeEmpty();
});

test('budget vs actual tracks expense against category budget', function () {
    ['owner' => $owner, 'personal' => $entity] = reportSetup();
    $category = catExpense($entity);
    $period = '2026-08';

    Budget::create([
        'entity_id' => $entity->id,
        'category_id' => $category->id,
        'period' => $period,
        'amount' => 1_500_000,
    ]);

    app(TransactionService::class)->createExpense($entity, $owner, [
        'date' => '2026-08-15',
        'amount' => 400_000,
        'account_id' => kas($entity)->id,
        'category_id' => $category->id,
    ]);

    $comparison = app(ReportService::class)->budgetVsActual($entity, $period);

    expect($comparison['items'])->toHaveCount(1)
        ->and((float) $comparison['items'][0]['budget'])->toBe(1_500_000.0)
        ->and((float) $comparison['items'][0]['actual'])->toBe(400_000.0)
        ->and((float) $comparison['items'][0]['remaining'])->toBe(1_100_000.0);
});

test('owner can manage budgets and view personal reports', function () {
    ['owner' => $owner, 'personal' => $entity] = reportSetup();
    $category = catExpense($entity);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/budgets', [
            'category_id' => $category->id,
            'period' => '2026-08',
            'amount' => 2_000_000,
        ])
        ->assertRedirect();

    expect(Budget::count())->toBe(1);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/reports?type=cash_flow&start=2026-01-01&end=2026-08-31')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->where('type', 'cash_flow')
            ->has('report.totals'));
});

test('business reports include profit loss and balanced balance sheet page', function () {
    ['owner' => $owner, 'business' => $entity] = reportSetup();

    app(TransactionService::class)->createIncome($entity, $owner, [
        'date' => '2026-08-01',
        'amount' => 2_000_000,
        'account_id' => kas($entity)->id,
        'category_id' => catIncome($entity)->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/reports?type=balance_sheet&as_of=2026-08-31')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->where('report.totals.is_balanced', true));

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/reports?type=profit_loss&start=2026-08-01&end=2026-08-31')
        ->assertSuccessful();
});

test('project profitability aggregates tagged transactions', function () {
    ['owner' => $owner, 'business' => $entity] = reportSetup();

    $client = Client::create([
        'entity_id' => $entity->id,
        'name' => 'Client X',
    ]);

    $project = Project::create([
        'entity_id' => $entity->id,
        'client_id' => $client->id,
        'name' => 'App Build',
        'status' => 'active',
        'budget' => 10_000_000,
    ]);

    $service = app(TransactionService::class);

    $service->createIncome($entity, $owner, [
        'date' => '2026-08-01',
        'amount' => 5_000_000,
        'account_id' => kas($entity)->id,
        'category_id' => catIncome($entity)->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
    ]);

    $service->createExpense($entity, $owner, [
        'date' => '2026-08-05',
        'amount' => 1_500_000,
        'account_id' => kas($entity)->id,
        'category_id' => catExpense($entity, 'Operasional')->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
    ]);

    $rows = app(ReportService::class)->projectProfitability($entity);

    $row = collect($rows)->firstWhere('id', $project->id);

    expect($row)->not->toBeNull()
        ->and((float) $row['revenue'])->toBe(5_000_000.0)
        ->and((float) $row['cost'])->toBe(1_500_000.0)
        ->and((float) $row['profit'])->toBe(3_500_000.0);
});

test('exports pdf and excel for cash flow', function () {
    ['owner' => $owner, 'personal' => $entity] = reportSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/reports/export?type=cash_flow&format=pdf&start=2026-01-01&end=2026-08-31')
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/reports/export?type=cash_flow&format=excel&start=2026-01-01&end=2026-08-31')
        ->assertSuccessful();
});

test('viewer cannot create budget', function () {
    ['viewer' => $viewer, 'business' => $entity] = reportSetup();
    $category = catExpense($entity);

    $this->actingAs($viewer)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/budgets', [
            'category_id' => $category->id,
            'period' => '2026-08',
            'amount' => 100000,
        ])
        ->assertForbidden();
});
