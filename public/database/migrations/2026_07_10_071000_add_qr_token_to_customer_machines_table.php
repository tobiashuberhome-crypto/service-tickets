<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_machines', function (Blueprint $table): void {
            $table->string('qr_token', 32)->nullable()->unique()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('customer_machines', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};