<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('contact_info')->nullable(); // email, phone, alamat (free-form)
            $table->timestamps();

            $table->index('entity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
