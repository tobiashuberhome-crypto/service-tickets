<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_part_eans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('spare_part_id')->constrained()->cascadeOnDelete();
            $table->string('ean')->unique();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['spare_part_id', 'ean']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_eans');
    }
};
