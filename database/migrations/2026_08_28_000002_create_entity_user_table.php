<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_user', function (Blueprint $table) {
            $table->id(); // bigint OK — pivot internal, tidak ter-expose ke URL
            $table->foreignUuid('entity_id')->constrained()->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained()->onDelete('restrict');
            $table->enum('role', ['owner', 'member', 'viewer']);
            $table->timestamps();

            $table->unique(['entity_id', 'user_id']);
            $table->index('entity_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_user');
    }
};
