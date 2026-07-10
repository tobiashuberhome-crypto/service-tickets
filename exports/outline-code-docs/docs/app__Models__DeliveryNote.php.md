# Datei: app\Models\DeliveryNote.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\DeliveryNote.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'disk', 'path', 'created_by'];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'delivery_note_ticket')->withTimestamps();
    }
}

```
