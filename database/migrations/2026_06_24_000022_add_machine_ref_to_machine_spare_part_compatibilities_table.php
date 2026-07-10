<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_spare_part_compatibilities', function (Blueprint $table): void {
            // Add machine_ref as a nullable string first
            $table->string('machine_ref', 191)->nullable()->index()->after('machine_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('machine_spare_part_compatibilities', function (Blueprint $table): void {
            $table->dropIndex(['machine_ref']);
            $table->dropColumn('machine_ref');
        });
    }
};
