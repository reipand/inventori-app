<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 50)->unique();
            $table->string('name', 255);
            $table->uuid('category_id');
            $table->string('unit', 50);
            $table->decimal('buy_price', 15, 2);
            $table->decimal('sell_price', 15, 2);
            $table->integer('min_stock')->default(0);
            $table->integer('current_stock')->default(0);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();

            $table->index('sku');
            $table->index('category_id');
            $table->index('current_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
