# Datei: app\Models\Ticket.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Models\Ticket.php`
- **Stand:** 2026-06-27 20:18:36
- **Typ:** php

## Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_INTERNALLY_DONE = 'internally_done';
    public const STATUS_DONE = 'done';
    public const STATUS_DELIVERED = 'delivered';

    public const SYNC_PENDING = 'pending';
    public const SYNC_SYNCED = 'synced';
    public const SYNC_ERROR = 'error';

    protected $fillable = [
        'ticket_number',
        'dolibarr_order_id',
        'dolibarr_order_ref',
        'dolibarr_invoice_id',
        'dolibarr_invoice_ref',
        'dolibarr_customer_id',
        'customer_name_snapshot',
        'customer_contact_name_snapshot',
        'customer_email_snapshot',
        'customer_machine_id',
        'customer_machine_profile_id',
        'created_via_customer_portal',
        'customer_portal_account_id',
        'service_enabled',
        'repair_enabled',
        'spare_part_order_required',
        'customer_portal_estimate_lines',
        'customer_portal_estimate_total',
        'error_description',
        'customer_photo_path',
        'acceptance_date',
        'target_date',
        'target_sort_order',
        'status',
        'sync_status',
        'sync_message',
        'completed_at',
        'cleaning',
    ];

    protected $casts = [
        'service_enabled' => 'boolean',
        'repair_enabled' => 'boolean',
        'spare_part_order_required' => 'boolean',
        'created_via_customer_portal' => 'boolean',
        'customer_portal_estimate_lines' => 'array',
        'customer_portal_estimate_total' => 'decimal:2',
        'acceptance_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'cleaning' => 'boolean',
        'dolibarr_invoice_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = 'T-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
            }
        });
    }

    public function customerMachine(): BelongsTo
    {
        return $this->belongsTo(CustomerMachine::class);
    }

    public function customerMachineProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerMachineProfile::class);
    }

    public function customerPortalAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerPortalAccount::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(TicketPart::class);
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(TicketServiceLine::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function syncStatusLabel(): string
    {
        return [
            self::SYNC_PENDING => 'ausstehend',
            self::SYNC_SYNCED => 'synchronisiert',
            self::SYNC_ERROR => 'Fehler',
        ][$this->sync_status] ?? $this->sync_status;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'offen',
            self::STATUS_IN_PROGRESS => 'in Bearbeitung',
            self::STATUS_INTERNALLY_DONE => 'intern erledigt',
            self::STATUS_DONE => 'erledigt',
            self::STATUS_DELIVERED => 'geliefert',
        ];
    }

    public function isDone(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_DELIVERED], true);
    }

    public function markSyncError(string $message): void
    {
        $this->forceFill([
            'sync_status' => self::SYNC_ERROR,
            'sync_message' => Str::limit($message, 500),
        ])->save();
    }

    public function markSynced(?Carbon $completedAt = null): void
    {
        $this->forceFill([
            'sync_status' => self::SYNC_SYNCED,
            'sync_message' => null,
            'completed_at' => $completedAt ?? $this->completed_at,
        ])->save();
    }
}

```
