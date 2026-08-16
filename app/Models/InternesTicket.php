<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InternesTicket extends Model
{
    protected $table = 'interne_tickets';

    protected $fillable = [
        'ticket_number',
        'quelle',
        'typ',
        'titel',
        'beschreibung',
        'prioritaet',
        'status',
        'ersteller_name',
        'ersteller_email',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            $ticket->ticket_number = 'INT-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        });
    }

    public function quelleLabel(): string
    {
        return match ($this->quelle) {
            'lager'         => 'Lager',
            'zeitebuchung'  => 'Zeitebuchung',
            default         => $this->quelle,
        };
    }

    public function prioritaetBadgeClass(): string
    {
        return match ($this->prioritaet) {
            'hoch'    => 'badge-danger',
            'mittel'  => 'badge-warning',
            'niedrig' => 'badge-success',
            default   => '',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'offen'         => 'badge-secondary',
            'in_bearbeitung' => 'badge-primary',
            'erledigt'      => 'badge-success',
            default         => '',
        };
    }
}
