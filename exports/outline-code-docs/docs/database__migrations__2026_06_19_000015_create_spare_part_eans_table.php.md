# Datei: database\migrations\2026_06_19_000015_create_spare_part_eans_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000015_create_spare_part_eans_table.php`
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
        Schema::create('spare_part_eans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('spare_part_id')->constrained()->cascadeOnDelete();
            $table->string('ean')->unique();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['spare_part_id', 'ean']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_eans');
    }
};

```
