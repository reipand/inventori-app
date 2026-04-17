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
            $table->uuid('product_id');
            $table->enum('type', ['masuk', 'keluar']);
            $table->integer('quantity');
            $table->decimal('price_per_unit', 15, 2);
            $table->string('supplier_name', 255)->nullable();
            $table->date('transaction_date');
            $table->uuid('recorded_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();

            $table->index('product_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
