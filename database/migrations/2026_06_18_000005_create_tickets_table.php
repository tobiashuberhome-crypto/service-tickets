<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('dolibarr_order_id')->nullable()->index();
            $table->string('dolibarr_order_ref')->nullable()->index();
            $table->unsignedBigInteger('dolibarr_customer_id')->index();
            $table->string('customer_name_snapshot');
            $table->foreignId('customer_machine_id')->constrained()->restrictOnDelete();
            $table->boolean('service_enabled')->default(false);
            $table->boolean('cleaning')->default(false);
            $table->boolean('repair_enabled')->default(false);
            $table->text('error_description')->nullable();
            $table->date('acceptance_date');
            $table->date('target_date')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->string('sync_status', 30)->default('pending')->index();
            $table->text('sync_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
