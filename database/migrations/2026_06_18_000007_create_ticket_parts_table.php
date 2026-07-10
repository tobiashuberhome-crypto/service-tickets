<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_part_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('part_ref_snapshot');
            $table->string('label_snapshot');
            $table->text('description_snapshot')->nullable();
            $table->decimal('purchase_price_snapshot', 12, 2)->nullable();
            $table->decimal('sales_price_snapshot', 12, 2)->default(0);
            $table->decimal('vat_rate_snapshot', 5, 2)->default(19);
            $table->string('unit_snapshot', 20)->default('Stk');
            $table->unsignedBigInteger('dolibarr_order_line_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_parts');
    }
};
