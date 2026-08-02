<?php

namespace App\Http\Controllers;

use App\Models\InternesTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternesTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = InternesTicket::query()->orderByDesc('created_at');

        if ($request->filled('quelle')) {
            $query->where('quelle', $request->quelle);
        }
        if ($request->filled('typ')) {
            $query->where('typ', $request->typ);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(50)->withQueryString();

        return view('interne-tickets.index', compact('tickets'));
    }

    public function updateStatus(Request $request, InternesTicket $internesTicket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:offen,in_bearbeitung,erledigt'],
        ]);

        $internesTicket->update(['status' => $request->status]);

        return back()->with('success', 'Status aktualisiert.');
    }
}
