# Datei: database\migrations\2026_06_24_000018_add_portal_scope_to_customer_portal_accounts_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_24_000018_add_portal_scope_to_customer_portal_accounts_table.php`
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
        Schema::table('customer_portal_accounts', function (Blueprint $table): void {
            $table->string('portal_scope', 40)->default('default')->after('password')->index();
        });

        DB::table('customer_portal_accounts')
            ->where('dolibarr_thirdparty_id', 9)
            ->update(['portal_scope' => 'geiser']);
    }

    public function down(): void
    {
        Schema::table('customer_portal_accounts', function (Blueprint $table): void {
            $table->dropColumn('portal_scope');
        });
    }
};

```
