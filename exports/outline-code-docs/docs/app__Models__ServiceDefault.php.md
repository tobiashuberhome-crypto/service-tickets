# Datei: app\Models\ServiceDefault.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\ServiceDefault.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_ref',
        'label',
        'quantity',
        'active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'active' => 'boolean',
    ];
}

```
