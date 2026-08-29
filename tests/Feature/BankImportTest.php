<?php

use App\Models\Account;
use App\Models\BankImportRule;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ImportService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function importSetup(): array
{
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $entity = Entity::create(['name' => 'Personal', 'type' => 'personal']);
    $entity->users()->attach($owner->id, ['role' => 'owner']);

    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);
    $business->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($viewer->id, ['role' => 'viewer']);

    (new ChartOfAccountsSeeder)->run();

    return compact('owner', 'viewer', 'entity', 'business');
}

test('csv preview auto assigns category from keyword rule', function () {
    ['owner' => $owner, 'entity' => $entity] = importSetup();

    $transport = Category::where('entity_id', $entity->id)->where('name', 'Transport')->firstOrFail();

    BankImportRule::create([
        'entity_id' => $entity->id,
        'keyword' => 'GOJEK',
        'category_id' => $transport->id,
    ]);

    $csv = "date,description,amount\n2026-08-01,GOJEK RIDE JAKARTA,-25000\n2026-08-02,TRANSFER GAJI,5000000\n";
    $file = UploadedFile::fake()->createWithContent('mutasi.csv', $csv);

    $preview = app(ImportService::class)->preview($entity, $file);

    expect($preview)->toHaveCount(2)
        ->and($preview[0]['type'])->toBe('expense')
        ->and($preview[0]['category_id'])->toBe($transport->id)
        ->and($preview[0]['matched_keyword'])->toBe('GOJEK')
        ->and($preview[1]['type'])->toBe('income')
        ->and($preview[1]['category_id'])->toBeNull();
});

test('confirm import creates draft transactions via transaction service', function () {
    ['owner' => $owner, 'entity' => $entity] = importSetup();

    $kas = Account::where('entity_id', $entity->id)->where('name', 'Kas')->firstOrFail();
    $transport = Category::where('entity_id', $entity->id)->where('name', 'Transport')->firstOrFail();
    $gaji = Category::where('entity_id', $entity->id)->where('name', 'Gaji/Fee')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/import/confirm', [
            'account_id' => $kas->id,
            'file_name' => 'mutasi.csv',
            'rows' => [
                [
                    'date' => '2026-08-01',
                    'description' => 'GOJEK RIDE',
                    'amount' => 25000,
                    'type' => 'expense',
                    'category_id' => $transport->id,
                    'include' => true,
                ],
                [
                    'date' => '2026-08-02',
                    'description' => 'GAJI',
                    'amount' => 5000000,
                    'type' => 'income',
                    'category_id' => $gaji->id,
                    'include' => true,
                ],
            ],
        ])
        ->assertRedirect(route('import.index'));

    expect(Transaction::count())->toBe(2)
        ->and(Transaction::where('status', 'draft')->count())->toBe(2)
        ->and(Transaction::whereNotNull('bank_import_batch_id')->count())->toBe(2);

    $expense = Transaction::where('type', 'expense')->with('entries')->first();
    expect($expense->entries)->toHaveCount(2);
    expect((float) $expense->entries->sum('debit'))->toBe((float) $expense->entries->sum('kredit'));
});

test('owner can manage import rules', function () {
    ['owner' => $owner, 'entity' => $entity] = importSetup();
    $transport = Category::where('entity_id', $entity->id)->where('name', 'Transport')->firstOrFail();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/import/rules', [
            'keyword' => 'GRAB',
            'category_id' => $transport->id,
        ])
        ->assertRedirect();

    expect(BankImportRule::where('keyword', 'GRAB')->exists())->toBeTrue();
});

test('viewer cannot access import', function () {
    ['viewer' => $viewer, 'business' => $business] = importSetup();

    $this->actingAs($viewer)
        ->withSession(['active_entity_id' => $business->id])
        ->get('/import')
        ->assertForbidden();
});

test('csv with debit credit columns is parsed correctly', function () {
    ['entity' => $entity] = importSetup();

    $csv = "tanggal,keterangan,debit,kredit\n01/08/2026,BELI MAKAN,50000,\n02/08/2026,TERIMA FEE,,1500000\n";
    $file = UploadedFile::fake()->createWithContent('bank.csv', $csv);

    $rows = app(ImportService::class)->parseCsv($file);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['type'])->toBe('expense')
        ->and($rows[0]['amount'])->toBe(50000.0)
        ->and($rows[0]['date'])->toBe('2026-08-01')
        ->and($rows[1]['type'])->toBe('income')
        ->and($rows[1]['amount'])->toBe(1500000.0);
});
