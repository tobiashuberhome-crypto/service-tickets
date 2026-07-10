# Datei: app\Models\TicketPart.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\TicketPart.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'spare_part_id',
        'quantity',
        'part_ref_snapshot',
        'label_snapshot',
        'description_snapshot',
        'purchase_price_snapshot',
        'sales_price_snapshot',
        'vat_rate_snapshot',
        'unit_snapshot',
        'dolibarr_order_line_id',
        'stock_movement_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'purchase_price_snapshot' => 'decimal:2',
        'sales_price_snapshot' => 'decimal:2',
        'vat_rate_snapshot' => 'decimal:2',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(SparePartStockMovement::class);
    }

    public function totalNet(): float
    {
        return (float) $this->quantity * (float) $this->sales_price_snapshot;
    }
}

```
