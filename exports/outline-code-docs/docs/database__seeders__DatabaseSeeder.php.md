# Datei: database\seeders\DatabaseSeeder.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `database\seeders\DatabaseSeeder.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace Database\Seeders;

use App\Models\ServiceDefault;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['NM-Klein', 'NM-Service', 'VDE'] as $ref) {
            ServiceDefault::query()->updateOrCreate(
                ['product_ref' => $ref],
                ['label' => $ref, 'quantity' => 1, 'active' => true]
            );
        }
    }
}

```
