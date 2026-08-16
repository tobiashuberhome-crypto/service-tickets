# Datei: database\migrations\2026_06_18_000006_create_ticket_service_lines_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_18_000006_create_ticket_service_lines_table.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_service_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_default_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_ref');
            $table->string('label_snapshot');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('sales_price_snapshot', 12, 2)->nullable();
            $table->decimal('vat_rate_snapshot', 5, 2)->default(19);
            $table->unsignedBigInteger('dolibarr_order_line_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['ticket_id', 'product_ref'], 'ticket_service_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_service_lines');
    }
};

```
