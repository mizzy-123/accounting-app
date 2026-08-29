<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();

            // FKs yang belum ada tabelnya (Sprint 4) — stored as nullable string UUID
            $table->char('project_id', 36)->nullable()->index();
            $table->char('client_id', 36)->nullable()->index();

            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('recurring_transaction_id')->nullable()->constrained('recurring_transactions')->nullOnDelete();

            $table->date('date');
            $table->text('description')->nullable();

            $table->enum('type', [
                'income',
                'expense',
                'transfer',
                'inter_entity_transfer',
                'adjustment',
            ]);

            // Convenience field: jumlah yang diinput user (untuk list/laporan cepat).
            // Tidak menggantikan validasi SUM(debit)=SUM(kredit) di entries.
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('status', ['draft', 'pending_approval', 'approved'])->default('draft');

            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->string('reference')->nullable(); // untuk inter-entity transfer linking
            $table->timestamps();

            $table->index(['entity_id', 'date']);
            $table->index(['entity_id', 'status']);
            $table->index(['entity_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
