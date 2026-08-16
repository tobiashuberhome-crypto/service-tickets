<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table): void {
            $table->id();
            $table->string('part_ref')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable()->index();
            $table->string('supplier')->nullable();
            $table->string('supplier_ref')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(19);
            $table->string('unit', 20)->default('Stk');
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['part_ref', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
