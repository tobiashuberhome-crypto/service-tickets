<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interne_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique();
            $table->enum('quelle', ['lager', 'zeitebuchung']);
            $table->enum('typ', ['bug', 'feature', 'aufgabe']);
            $table->string('titel', 255);
            $table->text('beschreibung')->nullable();
            $table->enum('prioritaet', ['niedrig', 'mittel', 'hoch'])->default('mittel');
            $table->enum('status', ['offen', 'in_bearbeitung', 'erledigt'])->default('offen');
            $table->string('ersteller_name', 255);
            $table->string('ersteller_email', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interne_tickets');
    }
};
