# Datei: database\migrations\2026_06_18_000008_create_machine_documents_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_18_000008_create_machine_documents_table.php`
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
        Schema::create('machine_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('machine_product_id')->index();
            $table->string('title');
            $table->text('url');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_documents');
    }
};

```
