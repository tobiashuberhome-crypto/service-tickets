# Datei: database\migrations\2026_06_19_000017_add_customer_portal_fields_to_tickets_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000017_add_customer_portal_fields_to_tickets_table.php`
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
            $table->boolean('created_via_customer_portal')->default(false)->after('customer_machine_id');
            $table->foreignId('customer_portal_account_id')->nullable()->after('created_via_customer_portal')->constrained()->nullOnDelete();
            $table->string('customer_contact_name_snapshot')->nullable()->after('customer_name_snapshot');
            $table->string('customer_email_snapshot')->nullable()->after('customer_contact_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_portal_account_id');
            $table->dropColumn([
                'created_via_customer_portal',
                'customer_contact_name_snapshot',
                'customer_email_snapshot',
            ]);
        });
    }
};

```
