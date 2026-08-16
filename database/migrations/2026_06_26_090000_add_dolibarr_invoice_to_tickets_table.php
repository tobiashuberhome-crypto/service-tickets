<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('dolibarr_invoice_id')->nullable()->index()->after('dolibarr_order_ref');
            $table->string('dolibarr_invoice_ref')->nullable()->index()->after('dolibarr_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['dolibarr_invoice_id', 'dolibarr_invoice_ref']);
        });
    }
};
