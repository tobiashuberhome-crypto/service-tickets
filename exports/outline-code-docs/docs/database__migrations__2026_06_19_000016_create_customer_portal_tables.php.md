# Datei: database\migrations\2026_06_19_000016_create_customer_portal_tables.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_19_000016_create_customer_portal_tables.php`
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
        Schema::create('customer_portal_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->string('zip')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('machine_serial')->nullable()->index();
            $table->string('customer_number_input')->nullable()->index();
            $table->string('invoice_or_order_number')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 40)->default('neu')->index();
            $table->unsignedBigInteger('matched_dolibarr_thirdparty_id')->nullable()->index();
            $table->string('matched_dolibarr_customer_code')->nullable();
            $table->string('matched_customer_name')->nullable();
            $table->text('review_note')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_portal_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dolibarr_thirdparty_id')->index();
            $table->string('dolibarr_customer_code')->nullable()->index();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_portal_magic_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_portal_account_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_magic_links');
        Schema::dropIfExists('customer_portal_accounts');
        Schema::dropIfExists('customer_portal_requests');
    }
};

```
