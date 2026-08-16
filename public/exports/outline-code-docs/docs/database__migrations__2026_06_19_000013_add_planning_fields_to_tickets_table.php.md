# Datei: database\migrations\2026_06_19_000013_add_planning_fields_to_tickets_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000013_add_planning_fields_to_tickets_table.php`
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
            $table->boolean('spare_part_order_required')->default(false)->after('repair_enabled')->index();
            $table->unsignedInteger('target_sort_order')->default(0)->after('target_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['spare_part_order_required', 'target_sort_order']);
        });
    }
};

```
