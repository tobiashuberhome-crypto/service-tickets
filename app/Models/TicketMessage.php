<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use HasFactory;

    public const SENDER_ADMIN = 'admin';
    public const SENDER_CUSTOMER = 'customer';

    protected $fillable = [
        'ticket_id',
        'customer_portal_account_id',
        'sender_type',
        'sender_label',
        'body',
        'sent_to_customer_at',
    ];

    protected $casts = [
        'sent_to_customer_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customerPortalAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerPortalAccount::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketMessageAttachment::class);
    }
}

