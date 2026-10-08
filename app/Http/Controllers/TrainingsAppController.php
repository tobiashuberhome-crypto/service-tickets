<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingsAppController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::query()
            ->where('created_via_customer_portal', true)
            ->with(['messages.attachments'])
            ->whereHas('customerPortalAccount', function ($q) {
                $q->where('portal_scope', 'default');
            })
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(50)->withQueryString();

        return view('trainings-app.index', [
            'tickets' => $tickets,
            'statuses' => Ticket::statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Ticket::statusOptions()))],
        ]);

        $ticket->update(['status' => $request->status]);

        return back()->with('success', 'Status aktualisiert.');
    }
}
