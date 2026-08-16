<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE machine_spare_part_compatibilities MODIFY machine_product_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE machine_spare_part_compatibilities MODIFY machine_product_id BIGINT UNSIGNED NOT NULL');
    }
};

