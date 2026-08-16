# Datei: database\migrations\2026_06_27_000030_add_photo_to_tickets_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_27_000030_add_photo_to_tickets_table.php`
- **Stand:** 2026-06-27 20:18:22
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
            $table->string('customer_photo_path')->nullable()->after('error_description');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('customer_photo_path');
        });
    }
};
```
