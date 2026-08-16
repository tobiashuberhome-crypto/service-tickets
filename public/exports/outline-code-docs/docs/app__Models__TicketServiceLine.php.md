# Datei: app\Models\TicketServiceLine.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\TicketServiceLine.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketServiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'service_default_id',
        'product_ref',
        'label_snapshot',
        'quantity',
        'sales_price_snapshot',
        'vat_rate_snapshot',
        'dolibarr_order_line_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sales_price_snapshot' => 'decimal:2',
        'vat_rate_snapshot' => 'decimal:2',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function serviceDefault(): BelongsTo
    {
        return $this->belongsTo(ServiceDefault::class);
    }
}

```
