<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignUuid('asset_account_id')->constrained('accounts');
            $table->foreignUuid('accumulated_account_id')->constrained('accounts');
            $table->foreignUuid('expense_account_id')->constrained('accounts');
            $table->foreignUuid('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('acquisition_date');
            $table->decimal('cost', 15, 2);
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('method')->default('straight_line');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
