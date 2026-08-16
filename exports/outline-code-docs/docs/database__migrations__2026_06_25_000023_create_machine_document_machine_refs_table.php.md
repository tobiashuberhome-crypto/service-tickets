# Datei: database\migrations\2026_06_25_000023_create_machine_document_machine_refs_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_25_000023_create_machine_document_machine_refs_table.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_document_machine_refs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('machine_document_id')->constrained('machine_documents')->cascadeOnDelete();
            $table->string('machine_ref', 191)->index();
            $table->timestamps();

            $table->unique(['machine_document_id', 'machine_ref'], 'machine_document_ref_unique');
        });

        DB::table('machine_documents')
            ->select(['id', 'machine_product_id'])
            ->orderBy('id')
            ->chunkById(200, function ($documents): void {
                foreach ($documents as $document) {
                    $refs = DB::table('customer_machines')
                        ->where('dolibarr_machine_product_id', (int) $document->machine_product_id)
                        ->whereNotNull('machine_ref_snapshot')
                        ->where('machine_ref_snapshot', '!=', '')
                        ->distinct()
                        ->pluck('machine_ref_snapshot');

                    $rows = [];
                    foreach ($refs as $ref) {
                        $rows[] = [
                            'machine_document_id' => (int) $document->id,
                            'machine_ref' => (string) $ref,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('machine_document_machine_refs')->insertOrIgnore($rows);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_document_machine_refs');
    }
};


```
