<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products');
            $table->integer('qty');
            $table->decimal('price_input', 15, 2);
            $table->enum('price_mode', ['final', 'before_discount'])->default('final');
            $table->enum('discount_item_type', ['percent', 'nominal'])->nullable();
            $table->decimal('discount_item_value', 15, 2)->default(0);
            $table->decimal('price_per_unit_final', 15, 2);
            $table->decimal('global_discount_portion', 15, 2)->default(0);
            $table->decimal('cogs_per_unit', 15, 2);
            $table->decimal('subtotal_final', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
