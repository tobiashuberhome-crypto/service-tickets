# Datei: database\migrations\2026_06_24_000019_create_customer_machine_profiles_table.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\migrations\2026_06_24_000019_create_customer_machine_profiles_table.php`
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
        Schema::create('customer_machine_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dolibarr_customer_id')->index();
            $table->string('serial_number');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->string('zip', 40)->nullable();
            $table->string('city')->nullable();
            $table->string('manufacturer_snapshot')->nullable();
            $table->string('machine_ref_snapshot')->nullable();
            $table->boolean('warranty_claimed')->default(false);
            $table->boolean('accessory_presser_foot')->default(false);
            $table->boolean('accessory_bobbin_case')->default(false);
            $table->boolean('accessory_bobbin')->default(false);
            $table->boolean('accessory_power_cable')->default(false);
            $table->boolean('accessory_foot_pedal')->default(false);
            $table->boolean('accessory_case')->default(false);
            $table->string('accessory_other')->nullable();
            $table->decimal('repair_approval_limit', 10, 2)->nullable();
            $table->text('intake_note')->nullable();
            $table->timestamps();

            $table->unique(['dolibarr_customer_id', 'serial_number'], 'customer_machine_profiles_serial_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_machine_profiles');
    }
};

```
