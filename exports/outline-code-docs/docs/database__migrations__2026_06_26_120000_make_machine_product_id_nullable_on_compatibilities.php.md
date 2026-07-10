# Datei: database\migrations\2026_06_26_120000_make_machine_product_id_nullable_on_compatibilities.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_26_120000_make_machine_product_id_nullable_on_compatibilities.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE machine_spare_part_compatibilities MODIFY machine_product_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE machine_spare_part_compatibilities MODIFY machine_product_id BIGINT UNSIGNED NOT NULL');
    }
};


```
