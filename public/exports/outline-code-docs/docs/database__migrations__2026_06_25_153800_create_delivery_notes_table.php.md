# Datei: database\migrations\2026_06_25_153800_create_delivery_notes_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_25_153800_create_delivery_notes_table.php`
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
        Schema::create('delivery_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_note_ticket', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_ticket');
        Schema::dropIfExists('delivery_notes');
    }
};
```
