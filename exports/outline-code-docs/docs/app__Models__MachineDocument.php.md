# Datei: app\Models\MachineDocument.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\MachineDocument.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_ref',
        'machine_product_id',
        'title',
        'url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

```
