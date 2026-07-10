<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->boolean('spare_part_order_required')->default(false)->after('repair_enabled')->index();
            $table->unsignedInteger('target_sort_order')->default(0)->after('target_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['spare_part_order_required', 'target_sort_order']);
        });
    }
};
