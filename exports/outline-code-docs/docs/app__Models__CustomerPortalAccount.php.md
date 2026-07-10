# Datei: app\Models\CustomerPortalAccount.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\CustomerPortalAccount.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPortalAccount extends Model
{
    use HasFactory;

    public const PORTAL_SCOPE_DEFAULT = 'default';
    public const PORTAL_SCOPE_GEISER = 'geiser';

    protected $fillable = [
        'dolibarr_thirdparty_id',
        'dolibarr_customer_code',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'password',
        'portal_scope',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function magicLinks(): HasMany
    {
        return $this->hasMany(CustomerPortalMagicLink::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isDefaultPortal(): bool
    {
        return $this->portal_scope === self::PORTAL_SCOPE_DEFAULT;
    }

    public function isGeiserPortal(): bool
    {
        return $this->portal_scope === self::PORTAL_SCOPE_GEISER;
    }
}

```
