# Datei: database\migrations\2026_06_24_000022_add_pdf_fields_to_tickets_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_24_000022_add_pdf_fields_to_tickets_table.php`
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
            $table->string('pdf_template_key', 80)->nullable()->after('customer_portal_estimate_total');
            $table->string('pdf_layout_key', 80)->nullable()->after('pdf_template_key');
            $table->string('pdf_disk', 40)->nullable()->after('pdf_layout_key');
            $table->string('pdf_path')->nullable()->after('pdf_disk');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'pdf_template_key',
                'pdf_layout_key',
                'pdf_disk',
                'pdf_path',
                'pdf_generated_at',
            ]);
        });
    }
};

```
