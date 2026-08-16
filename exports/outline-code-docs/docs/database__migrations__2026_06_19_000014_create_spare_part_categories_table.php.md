# Datei: database\migrations\2026_06_19_000014_create_spare_part_categories_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000014_create_spare_part_categories_table.php`
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
        Schema::create('spare_part_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('spare_parts', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('description')->constrained('spare_part_categories')->nullOnDelete();
            $table->string('spare_part_type')->nullable()->after('category_id')->index();
            $table->string('manufacturer_part_number')->nullable()->after('supplier_ref')->index();
        });
    }

    public function down(): void
    {
        Schema::table('spare_parts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['spare_part_type', 'manufacturer_part_number']);
        });

        Schema::dropIfExists('spare_part_categories');
    }
};

```
