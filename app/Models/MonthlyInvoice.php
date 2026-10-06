<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MonthlyInvoice extends Model
{
    /**
     * Interne Monatsrechnungen von TH Services & Solutions (nur Admin, nie in einem Kundenportal).
     */
    public const SCOPE_TH_SERVICES = 'th_services';

    protected $fillable = [
        'portal_scope',
        'dolibarr_customer_id',
        'invoice_year',
        'invoice_month',
        'sequence_number',
        'generated_at',
    ];

    protected $casts = [
        'invoice_year' => 'integer',
        'invoice_month' => 'integer',
        'sequence_number' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'monthly_invoice_ticket')
            ->withTimestamps();
    }

    public function getMonthLabelAttribute(): string
    {
        return \DateTime::createFromFormat('!m', (string) $this->invoice_month)
            ->format('F').' '.$this->invoice_year;
    }

    public function getMonthKeyAttribute(): string
    {
        return sprintf('%04d-%02d', $this->invoice_year, $this->invoice_month);
    }

    public function getInvoiceNumberAttribute(): string
    {
        return sprintf('%s-%03d', $this->getMonthKeyAttribute(), $this->sequence_number);
    }

    public function getInvoiceLabelAttribute(): string
    {
        $monthLabel = $this->getMonthLabelAttribute();
        if ($this->sequence_number > 1) {
            return "{$monthLabel} (Rechnung #{$this->sequence_number})";
        }
        return $monthLabel;
    }
}
