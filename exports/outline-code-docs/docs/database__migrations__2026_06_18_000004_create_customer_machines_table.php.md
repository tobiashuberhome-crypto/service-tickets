# Datei: database\migrations\2026_06_18_000004_create_customer_machines_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_18_000004_create_customer_machines_table.php`
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
        Schema::create('customer_machines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dolibarr_customer_id')->index();
            $table->string('customer_name_snapshot');
            $table->unsignedBigInteger('dolibarr_machine_product_id')->index();
            $table->string('manufacturer_snapshot')->nullable();
            $table->string('machine_ref_snapshot');
            $table->string('serial_number')->nullable()->index();
            $table->timestamps();

            $table->index(['dolibarr_customer_id', 'dolibarr_machine_product_id'], 'customer_machine_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_machines');
    }
};

```
