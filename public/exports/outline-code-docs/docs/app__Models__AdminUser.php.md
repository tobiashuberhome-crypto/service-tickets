# Datei: app\Models\AdminUser.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\AdminUser.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];
}


```
