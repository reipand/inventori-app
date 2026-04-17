<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            $table->enum('action', ['create', 'update', 'delete']);
            $table->uuid('changed_by');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('changed_by')->references('id')->on('users')->restrictOnDelete();

            $table->index('created_at');
            $table->index('entity_type');
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
