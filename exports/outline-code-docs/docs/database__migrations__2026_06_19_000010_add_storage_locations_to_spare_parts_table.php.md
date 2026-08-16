# Datei: database\migrations\2026_06_19_000010_add_storage_locations_to_spare_parts_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000010_add_storage_locations_to_spare_parts_table.php`
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
        Schema::table('spare_parts', function (Blueprint $table): void {
            $table->string('storage_location_1')->nullable()->after('supplier_ref');
            $table->string('storage_location_2')->nullable()->after('storage_location_1');
        });
    }

    public function down(): void
    {
        Schema::table('spare_parts', function (Blueprint $table): void {
            $table->dropColumn(['storage_location_1', 'storage_location_2']);
        });
    }
};

```
