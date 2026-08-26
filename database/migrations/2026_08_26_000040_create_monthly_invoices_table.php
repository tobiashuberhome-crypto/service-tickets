<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('portal_scope')->index(); // geiser, cibena, school, default
            $table->unsignedBigInteger('dolibarr_customer_id')->index();
            $table->year('invoice_year');
            $table->unsignedTinyInteger('invoice_month'); // 1-12
            $table->unsignedSmallInteger('sequence_number')->default(1); // 1, 2, 3... fÃ¼r mehrfache Rechnungen im Monat
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            // Unique constraint: pro Kunde/Portal/Monat kann es mehrere Rechnungen geben (sequence_number unterscheidet sie)
            $table->unique(['portal_scope', 'dolibarr_customer_id', 'invoice_year', 'invoice_month', 'sequence_number'], 'monthly_inv_unique');
            $table->index(['dolibarr_customer_id', 'invoice_year', 'invoice_month'], 'monthly_inv_date_idx');
        });

        Schema::create('monthly_invoice_ticket', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monthly_invoice_id')->constrained('monthly_invoices')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['monthly_invoice_id', 'ticket_id']);
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_invoice_ticket');
        Schema::dropIfExists('monthly_invoices');
    }
};
