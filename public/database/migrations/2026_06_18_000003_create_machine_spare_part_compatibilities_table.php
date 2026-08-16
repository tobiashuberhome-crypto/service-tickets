<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_spare_part_compatibilities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('machine_product_id')->index();
            $table->foreignId('spare_part_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['machine_product_id', 'spare_part_id'], 'machine_part_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_spare_part_compatibilities');
    }
};
