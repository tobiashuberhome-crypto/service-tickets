# Datei: database\migrations\2026_06_18_000001_create_service_defaults_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_18_000001_create_service_defaults_table.php`
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
        Schema::create('service_defaults', function (Blueprint $table): void {
            $table->id();
            $table->string('product_ref')->unique();
            $table->string('label')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_defaults');
    }
};

```
