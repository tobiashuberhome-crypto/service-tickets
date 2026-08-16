# Datei: database\migrations\2026_06_26_121000_add_machine_ref_to_machine_documents_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_26_121000_add_machine_ref_to_machine_documents_table.php`
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
        Schema::table('machine_documents', function (Blueprint $table): void {
            $table->string('machine_ref')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('machine_documents', function (Blueprint $table): void {
            $table->dropIndex(['machine_ref']);
            $table->dropColumn('machine_ref');
        });
    }
};


```
