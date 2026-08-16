<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_defaults', function (Blueprint $table): void {
            $table->id();
            $table->string('product_ref')->unique();
            $table->string('label')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_defaults');
    }
};
