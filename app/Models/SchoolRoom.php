<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_portal_account_id',
        'name',
        'notes',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerPortalAccount::class, 'customer_portal_account_id');
    }

    public function machines(): HasMany
    {
        return $this->hasMany(CustomerMachine::class, 'school_room_id');
    }

    public function openTicketsCount(): int
    {
        return $this->machines()
            ->withCount(['tickets as open_tickets_count' => fn ($q) => $q->whereNotIn('status', [Ticket::STATUS_DONE, Ticket::STATUS_DELIVERED])])
            ->get()
            ->sum('open_tickets_count');
    }
}
