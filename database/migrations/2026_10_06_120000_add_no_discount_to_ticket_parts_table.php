<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->boolean('no_discount')->default(false)->after('unit_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->dropColumn('no_discount');
        });
    }
};
