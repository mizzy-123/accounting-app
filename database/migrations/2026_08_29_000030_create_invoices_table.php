<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users');

            // Nomor invoice unik per entity: INV-2026-001
            $table->string('invoice_number', 50);

            // Line items: [{ name, qty, price, subtotal }]
            $table->json('items');

            $table->decimal('subtotal', 15, 2)->default(0); // sum of (qty × price)
            $table->decimal('discount', 15, 2)->default(0); // diskon nominal
            $table->decimal('total', 15, 2)->default(0);    // subtotal - discount

            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');

            $table->date('issued_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Nomor invoice unik per entity
            $table->unique(['entity_id', 'invoice_number']);
            $table->index(['entity_id', 'status']);
            $table->index(['entity_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
