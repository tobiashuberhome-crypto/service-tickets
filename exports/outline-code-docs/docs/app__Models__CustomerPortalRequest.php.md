# Datei: app\Models\CustomerPortalRequest.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\CustomerPortalRequest.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPortalRequest extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'neu';
    public const STATUS_REVIEW = 'in_pruefung';
    public const STATUS_LINKED = 'verknuepft';
    public const STATUS_CREATE_CUSTOMER = 'neukunde_anzulegen';
    public const STATUS_REJECTED = 'abgelehnt';
    public const STATUS_QUESTION = 'rueckfrage';

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'street',
        'zip',
        'city',
        'machine_serial',
        'customer_number_input',
        'invoice_or_order_number',
        'message',
        'status',
        'matched_dolibarr_thirdparty_id',
        'matched_dolibarr_customer_code',
        'matched_customer_name',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'neu',
            self::STATUS_REVIEW => 'in Pruefung',
            self::STATUS_LINKED => 'verknuepft',
            self::STATUS_CREATE_CUSTOMER => 'Neukunde anzulegen',
            self::STATUS_REJECTED => 'abgelehnt',
            self::STATUS_QUESTION => 'Rueckfrage',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }
}

```
