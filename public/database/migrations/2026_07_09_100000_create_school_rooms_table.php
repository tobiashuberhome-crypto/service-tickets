<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_portal_account_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('customer_machines', function (Blueprint $table): void {
            $table->foreignId('school_room_id')->nullable()->constrained('school_rooms')->nullOnDelete()->after('id');
        });

        // Make dolibarr_customer_id nullable for EasyAppointments / school portal tickets
        Schema::table('customer_machines', function (Blueprint $table): void {
            $table->unsignedBigInteger('dolibarr_customer_id')->nullable()->change();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('dolibarr_customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_machines', function (Blueprint $table): void {
            $table->dropForeign(['school_room_id']);
            $table->dropColumn('school_room_id');
            $table->unsignedBigInteger('dolibarr_customer_id')->nullable(false)->change();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('dolibarr_customer_id')->nullable(false)->change();
        });

        Schema::dropIfExists('school_rooms');
    }
};
