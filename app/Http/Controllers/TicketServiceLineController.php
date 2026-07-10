<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketServiceLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketServiceLineController extends Controller
{
    public function update(Request $request, Ticket $ticket, TicketServiceLine $ticketServiceLine): RedirectResponse
    {
        if ($ticketServiceLine->ticket_id !== $ticket->id) {
            abort(404);
        }

        if ($ticket->isDone() || $ticketServiceLine->dolibarr_order_line_id) {
            return back()->with('warning', 'Diese Serviceleistung wurde bereits uebertragen und kann nicht mehr geaendert werden.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        $ticketServiceLine->update([
            'quantity' => $data['quantity'],
        ]);

        $ticket->forceFill(['sync_status' => Ticket::SYNC_PENDING])->save();

        return back()->with('status', 'Menge der Serviceleistung aktualisiert.');
    }
}
