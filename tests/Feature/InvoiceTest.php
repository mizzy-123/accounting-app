<?php

use App\Models\Client;
use App\Models\Entity;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invoiceSetup(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $business = Entity::create(['name' => 'Manifestasi', 'type' => 'business']);
    $personal = Entity::create(['name' => 'Personal', 'type' => 'personal']);

    $business->users()->attach($owner->id, ['role' => 'owner']);
    $business->users()->attach($member->id, ['role' => 'member']);
    $personal->users()->attach($owner->id, ['role' => 'owner']);

    $client = Client::create([
        'entity_id' => $business->id,
        'name' => 'Client A',
        'contact_info' => 'client@a.com',
    ]);

    $project = Project::create([
        'entity_id' => $business->id,
        'client_id' => $client->id,
        'name' => 'Website Redesign',
        'budget' => 10000000,
        'status' => 'active',
    ]);

    return compact('owner', 'member', 'business', 'personal', 'client', 'project');
}

test('owner can create invoice from project with auto total', function () {
    ['owner' => $owner, 'business' => $entity, 'project' => $project, 'client' => $client] = invoiceSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->post('/invoices', [
            'project_id' => $project->id,
            'client_id' => $client->id,
            'issued_date' => '2026-08-29',
            'due_date' => '2026-09-15',
            'discount' => 100000,
            'items' => [
                ['name' => 'Design', 'qty' => 1, 'price' => 2000000],
                ['name' => 'Development', 'qty' => 2, 'price' => 1500000],
            ],
        ])
        ->assertRedirect();

    $invoice = Invoice::first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe('draft')
        ->and((float) $invoice->subtotal)->toBe(5000000.0)
        ->and((float) $invoice->discount)->toBe(100000.0)
        ->and((float) $invoice->total)->toBe(4900000.0)
        ->and($invoice->invoice_number)->toStartWith('INV-');
});

test('owner can update invoice status to sent and paid', function () {
    ['owner' => $owner, 'business' => $entity, 'project' => $project, 'client' => $client] = invoiceSetup();

    $invoice = Invoice::create([
        'entity_id' => $entity->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'invoice_number' => 'INV-2026-001',
        'items' => [['name' => 'Fee', 'qty' => 1, 'price' => 1000000, 'subtotal' => 1000000]],
        'subtotal' => 1000000,
        'discount' => 0,
        'total' => 1000000,
        'status' => 'draft',
        'issued_date' => '2026-08-01',
        'due_date' => '2026-08-20',
    ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->patch("/invoices/{$invoice->id}/status", ['status' => 'sent'])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe('sent');

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->patch("/invoices/{$invoice->id}/status", ['status' => 'paid'])
        ->assertRedirect();

    $fresh = $invoice->fresh();
    expect($fresh->status)->toBe('paid')
        ->and($fresh->paid_at)->not->toBeNull();
});

test('member cannot update invoice status', function () {
    ['owner' => $owner, 'member' => $member, 'business' => $entity, 'project' => $project] = invoiceSetup();

    $invoice = Invoice::create([
        'entity_id' => $entity->id,
        'project_id' => $project->id,
        'created_by' => $owner->id,
        'invoice_number' => 'INV-2026-002',
        'items' => [['name' => 'Fee', 'qty' => 1, 'price' => 500000, 'subtotal' => 500000]],
        'subtotal' => 500000,
        'discount' => 0,
        'total' => 500000,
        'status' => 'draft',
        'issued_date' => now()->toDateString(),
    ]);

    $this->actingAs($member)
        ->withSession(['active_entity_id' => $entity->id])
        ->patch("/invoices/{$invoice->id}/status", ['status' => 'sent'])
        ->assertForbidden();
});

test('receivables page lists unpaid invoices ordered by due date', function () {
    ['owner' => $owner, 'business' => $entity, 'project' => $project, 'client' => $client] = invoiceSetup();

    Invoice::create([
        'entity_id' => $entity->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'invoice_number' => 'INV-2026-010',
        'items' => [['name' => 'A', 'qty' => 1, 'price' => 1000, 'subtotal' => 1000]],
        'subtotal' => 1000,
        'discount' => 0,
        'total' => 1000,
        'status' => 'sent',
        'issued_date' => '2026-08-01',
        'due_date' => '2026-09-30',
    ]);

    Invoice::create([
        'entity_id' => $entity->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'invoice_number' => 'INV-2026-011',
        'items' => [['name' => 'B', 'qty' => 1, 'price' => 2000, 'subtotal' => 2000]],
        'subtotal' => 2000,
        'discount' => 0,
        'total' => 2000,
        'status' => 'sent',
        'issued_date' => '2026-08-01',
        'due_date' => '2026-08-10',
    ]);

    Invoice::create([
        'entity_id' => $entity->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'created_by' => $owner->id,
        'invoice_number' => 'INV-2026-012',
        'items' => [['name' => 'C', 'qty' => 1, 'price' => 3000, 'subtotal' => 3000]],
        'subtotal' => 3000,
        'discount' => 0,
        'total' => 3000,
        'status' => 'paid',
        'issued_date' => '2026-08-01',
        'due_date' => '2026-08-05',
        'paid_at' => now(),
    ]);

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $entity->id])
        ->get('/invoices/receivables')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('invoices/receivables')
            ->has('invoices', 2)
            ->where('invoices.0.invoice_number', 'INV-2026-011')
            ->where('invoices.1.invoice_number', 'INV-2026-010')
            ->where('summary.count', 2));
});

test('personal entity cannot access invoices', function () {
    ['owner' => $owner, 'personal' => $personal] = invoiceSetup();

    $this->actingAs($owner)
        ->withSession(['active_entity_id' => $personal->id])
        ->get('/invoices')
        ->assertForbidden();
});
