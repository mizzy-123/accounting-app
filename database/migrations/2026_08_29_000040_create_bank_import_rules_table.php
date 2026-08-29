<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_import_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['entity_id', 'keyword']);
            $table->unique(['entity_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_import_rules');
    }
};
