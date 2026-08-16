# Datei: database\migrations\2026_06_19_000012_add_stock_movement_id_to_ticket_parts_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000012_add_stock_movement_id_to_ticket_parts_table.php`
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
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->foreignId('stock_movement_id')
                ->nullable()
                ->after('dolibarr_order_line_id')
                ->constrained('spare_part_stock_movements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_parts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_movement_id');
        });
    }
};

```
