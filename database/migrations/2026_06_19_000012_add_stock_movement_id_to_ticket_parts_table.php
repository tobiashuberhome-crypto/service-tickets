<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->foreignId('stock_movement_id')
                ->nullable()
                ->after('dolibarr_order_line_id')
                ->constrained('spare_part_stock_movements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_movement_id');
        });
    }
};
