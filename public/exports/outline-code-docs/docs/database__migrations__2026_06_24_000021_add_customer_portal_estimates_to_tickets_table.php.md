# Datei: database\migrations\2026_06_24_000021_add_customer_portal_estimates_to_tickets_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_24_000021_add_customer_portal_estimates_to_tickets_table.php`
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
        Schema::table('tickets', function (Blueprint $table): void {
            $table->json('customer_portal_estimate_lines')->nullable()->after('spare_part_order_required');
            $table->decimal('customer_portal_estimate_total', 12, 2)->nullable()->after('customer_portal_estimate_lines');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_portal_estimate_lines',
                'customer_portal_estimate_total',
            ]);
        });
    }
};

```
