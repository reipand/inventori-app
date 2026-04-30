<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products');
            $table->integer('qty');
            $table->decimal('sell_price', 15, 2);
            $table->decimal('cogs', 15, 2);
            $table->decimal('discount_per_item', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
