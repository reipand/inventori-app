<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number', 100)->unique();
            $table->string('supplier_name', 255);
            $table->date('invoice_date');
            $table->enum('discount_global_type', ['percent', 'nominal'])->nullable();
            $table->decimal('discount_global_value', 15, 2)->default(0);
            $table->decimal('total_before_discount', 15, 2);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('total_final', 15, 2);
            $table->foreignUuid('recorded_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
