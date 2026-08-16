<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_documents', function (Blueprint $table): void {
            $table->string('machine_ref')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('machine_documents', function (Blueprint $table): void {
            $table->dropIndex(['machine_ref']);
            $table->dropColumn('machine_ref');
        });
    }
};

